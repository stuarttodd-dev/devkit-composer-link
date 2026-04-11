<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Services;

use HalfShellStudios\ComposerLink\Support\Json;

/**
 * Persists per-developer state in packages-local.json (default).
 * Falls back to reading composer.local-packages.json for legacy `packages` only.
 *
 * @phpstan-type PackageEntry array{
 *     path: string,
 *     mode: 'bootstrap'|'override',
 *     symlink: bool,
 *     original_constraint: string|null,
 *     active_constraint: string,
 *     installed_as: 'require'|'require-dev'
 * }
 */
final class LocalOverrideStore
{
    private const string LEGACY_OVERRIDES_BASENAME = 'composer.local-packages.json';

    public function __construct(
        private readonly string $filePath,
    ) {
    }

    /**
     * @return array<string, PackageEntry>
     */
    public function all(): array
    {
        return $this->loadDocument()['packages'];
    }

    /**
     * @return array{packages: array<string, PackageEntry>}
     */
    private function loadDocument(): array
    {
        $primaryExists = is_file($this->filePath);
        $primary = $this->readDocumentFromPath($this->filePath);
        $primaryPackages = $primary['packages'] ?? [];
        $packages = $this->normalizePackages(is_array($primaryPackages) ? $primaryPackages : []);

        if ($packages === [] && ! $primaryExists) {
            $legacy = $this->legacyFilePath();
            if ($legacy !== null && is_file($legacy)) {
                $legacyData = Json::read($legacy);
                $legacyPackages = $legacyData['packages'] ?? [];
                $packages = $this->normalizePackages(is_array($legacyPackages) ? $legacyPackages : []);
            }
        }

        return [
            'packages' => $packages,
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, PackageEntry>
     */
    private function normalizePackages(array $raw): array
    {
        /** @var array<string, PackageEntry> $out */
        $out = [];
        foreach ($raw as $name => $entry) {
            if (! is_string($name) || ! is_array($entry)) {
                continue;
            }
            $out[$name] = $this->normalizeEntry($entry);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function readDocumentFromPath(string $path): array
    {
        return Json::read($path);
    }

    private function legacyFilePath(): ?string
    {
        $dir = dirname($this->filePath);
        $base = basename($this->filePath);
        if ($base === self::LEGACY_OVERRIDES_BASENAME) {
            return null;
        }

        return $dir . DIRECTORY_SEPARATOR . self::LEGACY_OVERRIDES_BASENAME;
    }

    /**
     * @return ?PackageEntry
     */
    public function get(string $packageName): ?array
    {
        return $this->all()[$packageName] ?? null;
    }

    /**
     * @param PackageEntry $entry
     */
    public function put(string $packageName, array $entry): void
    {
        $doc = $this->loadDocument();
        $doc['packages'][$packageName] = $this->normalizeEntry($entry);
        $this->saveDocument($doc['packages']);
    }

    public function forget(string $packageName): void
    {
        $doc = $this->loadDocument();
        unset($doc['packages'][$packageName]);
        $this->saveDocument($doc['packages']);
    }

    /**
     * @param array<string, PackageEntry> $packages
     */
    public function replaceAll(array $packages): void
    {
        $normalized = [];
        foreach ($packages as $name => $entry) {
            $normalized[$name] = $this->normalizeEntry($entry);
        }
        $this->saveDocument($normalized);
    }

    /**
     * @param array<string, PackageEntry> $packages
     */
    private function saveDocument(array $packages): void
    {
        $legacy = $this->legacyFilePath();

        if ($packages === []) {
            if (is_file($this->filePath)) {
                unlink($this->filePath);
            }
            if ($legacy !== null && is_file($legacy)) {
                unlink($legacy);
            }

            return;
        }

        $payload = ['packages' => $packages];
        Json::write($this->filePath, $payload);
        if ($legacy !== null && is_file($legacy)) {
            unlink($legacy);
        }
    }

    /**
     * @param array<string, mixed> $entry
     * @return PackageEntry
     */
    private function normalizeEntry(array $entry): array
    {
        $path = $entry['path'] ?? '';
        $active = $entry['active_constraint'] ?? '@dev';

        return [
            'path' => is_string($path) ? $path : '',
            'mode' => ($entry['mode'] ?? 'override') === 'bootstrap' ? 'bootstrap' : 'override',
            'symlink' => (bool) ($entry['symlink'] ?? true),
            'original_constraint' => isset($entry['original_constraint']) && is_string($entry['original_constraint'])
                ? $entry['original_constraint']
                : null,
            'active_constraint' => is_string($active) ? $active : '@dev',
            'installed_as' => ($entry['installed_as'] ?? 'require') === 'require-dev' ? 'require-dev' : 'require',
        ];
    }
}
