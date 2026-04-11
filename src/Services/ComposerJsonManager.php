<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Services;

use HalfShellStudios\ComposerLink\Support\Json;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;

final class ComposerJsonManager
{
    public function __construct(
        private readonly string $canonicalComposerJsonPath,
        private readonly string $workingComposerJsonPath,
        private readonly string $repositoryMarker,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    private function projectRoot(): string
    {
        return dirname($this->workingComposerJsonPath);
    }

    public function workingManifestExists(): bool
    {
        return is_file($this->workingComposerJsonPath);
    }

    /**
     * @return array<string, mixed>
     */
    public function readCanonical(): array
    {
        return Json::read($this->canonicalComposerJsonPath);
    }

    /**
     * Working manifest if present, otherwise the canonical composer.json (no writes).
     *
     * @return array<string, mixed>
     */
    public function read(): array
    {
        if ($this->workingManifestExists()) {
            return Json::read($this->workingComposerJsonPath);
        }

        return $this->readCanonical();
    }

    /**
     * Requirement from the committed composer.json only (for `composer link`).
     *
     * @return array{constraint: string, section: 'require'|'require-dev'}|null
     */
    public function getCanonicalPackageRequirement(string $packageName): ?array
    {
        return $this->findPackageRequirement(Json::read($this->canonicalComposerJsonPath), $packageName);
    }

    /**
     * Requirement from canonical or local manifest (for `composer add` duplicate check).
     *
     * @return array{constraint: string, section: 'require'|'require-dev'}|null
     */
    public function getAnyPackageRequirement(string $packageName): ?array
    {
        $canonical = $this->findPackageRequirement(Json::read($this->canonicalComposerJsonPath), $packageName);
        if ($canonical !== null) {
            return $canonical;
        }
        if (! $this->workingManifestExists()) {
            return null;
        }

        return $this->findPackageRequirement(Json::read($this->workingComposerJsonPath), $packageName);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{constraint: string, section: 'require'|'require-dev'}|null
     */
    private function findPackageRequirement(array $data, string $packageName): ?array
    {
        foreach (['require', 'require-dev'] as $section) {
            if (! isset($data[$section]) || ! is_array($data[$section])) {
                continue;
            }
            if (! isset($data[$section][$packageName])) {
                continue;
            }
            $constraint = $data[$section][$packageName];
            if (! is_string($constraint)) {
                continue;
            }

            return ['constraint' => $constraint, 'section' => $section];
        }

        return null;
    }

    public function setPackageRequirement(string $packageName, string $constraint, string $section): void
    {
        if ($section !== 'require' && $section !== 'require-dev') {
            throw new InvalidArgumentException('Section must be require or require-dev');
        }

        $this->ensureWorkingManifest();
        /** @var array<string, mixed> $data */
        $data = Json::read($this->workingComposerJsonPath);
        foreach (['require', 'require-dev'] as $s) {
            if (isset($data[$s]) && is_array($data[$s])) {
                unset($data[$s][$packageName]);
            }
        }
        if (! isset($data[$section]) || ! is_array($data[$section])) {
            $data[$section] = [];
        }
        /** @var array<string, string> $sectionData */
        $sectionData = $data[$section];
        $sectionData[$packageName] = $constraint;
        ksort($sectionData);
        $data[$section] = $sectionData;
        $this->writeWorking($data);
    }

    public function removePackageRequirement(string $packageName): void
    {
        $this->ensureWorkingManifest();
        $data = Json::read($this->workingComposerJsonPath);
        foreach (['require', 'require-dev'] as $section) {
            if (isset($data[$section]) && is_array($data[$section])) {
                unset($data[$section][$packageName]);
            }
        }
        $this->writeWorking($data);
    }

    /**
     * Rebuild path repositories owned by this tool and sync require constraints from the store.
     *
     * @param array<string, array<string, mixed>> $packages keyed by package name (store entries)
     */
    public function syncFromStore(array $packages): void
    {
        $this->ensureWorkingManifest();
        $data = Json::read($this->workingComposerJsonPath);
        $repos = $data['repositories'] ?? [];
        if (! is_array($repos)) {
            $repos = [];
        }

        $filtered = [];
        foreach ($repos as $repo) {
            if (! $this->isManagedRepository($repo)) {
                $filtered[] = $repo;
            }
        }

        ksort($packages);

        $prepend = [];
        foreach ($packages as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $path = $entry['path'] ?? '';
            if (! is_string($path) || $path === '') {
                continue;
            }
            $symlink = (bool) ($entry['symlink'] ?? true);
            $prepend[] = [
                'type' => 'path',
                'url' => $this->normalizeRepositoryUrl($path),
                'options' => [
                    'symlink' => $symlink,
                    $this->repositoryMarker => true,
                ],
            ];
        }

        $data['repositories'] = array_merge($prepend, $filtered);
        if ($data['repositories'] === []) {
            unset($data['repositories']);
        }

        $this->writeWorking($data);
    }

    /**
     * @param array<string, mixed> $repo
     */
    private function isManagedRepository(mixed $repo): bool
    {
        if (! is_array($repo)) {
            return false;
        }
        $options = $repo['options'] ?? null;
        if (! is_array($options)) {
            return false;
        }

        return isset($options[$this->repositoryMarker]) && $options[$this->repositoryMarker] === true;
    }

    private function normalizeRepositoryUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return $path;
        }

        if (str_starts_with($path, '/') || preg_match('#^[a-zA-Z]:[\\\\/]#', $path) === 1) {
            $resolved = realpath($path);
            if ($resolved !== false) {
                return $this->toProjectRelative($resolved);
            }

            return str_replace('\\', '/', $path);
        }

        $candidate = $this->projectRoot() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $resolved = realpath($candidate);
        if ($resolved !== false) {
            return $this->toProjectRelative($resolved);
        }

        return str_replace('\\', '/', $path);
    }

    private function toProjectRelative(string $absolutePath): string
    {
        $absolutePath = str_replace('\\', '/', $absolutePath);
        $base = str_replace('\\', '/', $this->projectRoot());
        $rel = $this->filesystem->makePathRelative($absolutePath, $base);

        return rtrim(str_replace('\\', '/', $rel), '/');
    }

    /**
     * Path or URL fragment written to path repositories and the override store.
     */
    public function repositoryUrlForPackagePath(string $absolutePackagePath): string
    {
        return $this->normalizeRepositoryUrl($absolutePackagePath);
    }

    /**
     * Copy composer.json (and composer.lock when present) to the working manifest paths.
     */
    public function bootstrapWorkingManifest(bool $overwrite): void
    {
        if ($this->workingManifestExists() && ! $overwrite) {
            throw new InvalidArgumentException(
                'Working manifest already exists: ' . $this->workingComposerJsonPath . '. Pass --force to overwrite.'
            );
        }

        $canonical = Json::read($this->canonicalComposerJsonPath);
        Json::write($this->workingComposerJsonPath, $canonical);

        $canonicalLock = self::lockPathForComposerJson($this->canonicalComposerJsonPath);
        $workingLock = self::lockPathForComposerJson($this->workingComposerJsonPath);
        if (is_file($canonicalLock)) {
            $this->filesystem->copy($canonicalLock, $workingLock, true);
        } elseif (is_file($workingLock)) {
            $this->filesystem->remove($workingLock);
        }
    }

    public static function lockPathForComposerJson(string $composerJsonPath): string
    {
        return pathinfo($composerJsonPath, PATHINFO_EXTENSION) === 'json'
            ? substr($composerJsonPath, 0, -4) . 'lock'
            : $composerJsonPath . '.lock';
    }

    private function ensureWorkingManifest(): void
    {
        if ($this->workingManifestExists()) {
            return;
        }

        if (! is_file($this->canonicalComposerJsonPath)) {
            throw new InvalidArgumentException(
                'Canonical composer.json not found: '
                . $this->canonicalComposerJsonPath
            );
        }

        $canonical = Json::read($this->canonicalComposerJsonPath);
        Json::write($this->workingComposerJsonPath, $canonical);

        $canonicalLock = self::lockPathForComposerJson($this->canonicalComposerJsonPath);
        $workingLock = self::lockPathForComposerJson($this->workingComposerJsonPath);
        if (is_file($canonicalLock)) {
            $this->filesystem->copy($canonicalLock, $workingLock, true);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeWorking(array $data): void
    {
        Json::write($this->workingComposerJsonPath, $data);
    }
}
