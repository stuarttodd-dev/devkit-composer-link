<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink;

use HalfShellStudios\ComposerLink\Contracts\ComposerLinkOutput;
use HalfShellStudios\ComposerLink\Services\ComposerJsonManager;
use HalfShellStudios\ComposerLink\Services\ComposerProcessRunner;
use HalfShellStudios\ComposerLink\Services\ConstraintResolver;
use HalfShellStudios\ComposerLink\Services\LocalOverrideStore;
use HalfShellStudios\ComposerLink\Services\PackageInspector;
use HalfShellStudios\ComposerLink\Support\PathHelper;

/**
 * Framework-agnostic workflow (Composer plugin commands, etc.).
 */
final class ComposerLinkTasks
{
    /**
     * @param array{
     *     overrides_file: string,
     *     composer_json: string,
     *     local_composer_json: string,
     *     composer_binary: string,
     *     repository_marker: string,
     *     default_symlink: bool,
     *     update_with_all_dependencies: bool
     * } $config
     */
    public function __construct(
        private readonly LocalOverrideStore $store,
        private readonly ComposerJsonManager $composer,
        private readonly ComposerProcessRunner $runner,
        private readonly PackageInspector $inspector,
        private readonly ConstraintResolver $resolver,
        private readonly array $config,
        private readonly string $projectRoot,
    ) {
    }

    public function link(
        string $packageName,
        string $qualifiedPath,
        ?string $userConstraint,
        bool $noUpdate,
        bool $noSymlink,
        ComposerLinkOutput $out,
    ): int {
        $requirement = $this->composer->getCanonicalPackageRequirement($packageName);
        if ($requirement === null) {
            $msg = "Package [{$packageName}] is not in the committed composer.json — "
                . '`composer link` only works for dependencies already listed there.';
            if ($this->composer->getAnyPackageRequirement($packageName) !== null) {
                $msg .= ' You have it in composer.local.json only (e.g. via `composer add`). '
                    . 'Options: (1) Add the same require line to committed composer.json, '
                    . 'then run `composer link` again; or (2) `composer unlink '
                    . $packageName . ' --remove` then `composer add` with the new path.';
            } else {
                $msg .= ' Use `composer add` (composer-link:add) to bootstrap a new dependency from a path.';
            }
            $out->error($msg);

            return 1;
        }

        $inspected = $this->inspector->inspect($qualifiedPath);
        if ($inspected->name !== $packageName) {
            $out->error(sprintf(
                'Package name mismatch. Expected [%s] but local composer.json has [%s].',
                $packageName,
                $inspected->name
            ));

            return 1;
        }

        $existing = $this->store->get($packageName);
        if ($existing !== null && $existing['mode'] === 'bootstrap') {
            $out->info(
                "Package [{$packageName}] was added via `composer add` — converting to override mode."
            );
        }

        $originalConstraint = $requirement['constraint'];
        $active = $this->resolver->resolveForLink($originalConstraint, $inspected, $userConstraint);

        $symlink = ! $noSymlink && ($this->config['default_symlink'] ?? true);

        $repoPath = $this->composer->repositoryUrlForPackagePath($inspected->path);

        $this->store->put($packageName, [
            'path' => $repoPath,
            'mode' => 'override',
            'symlink' => $symlink,
            'original_constraint' => $originalConstraint,
            'active_constraint' => $active,
            'installed_as' => $requirement['section'],
        ]);

        $this->composer->syncFromStore($this->store->all());

        if ($active !== $originalConstraint) {
            $this->composer->setPackageRequirement($packageName, $active, $requirement['section']);
        }

        if (! $noUpdate) {
            $this->runner->updatePackage($packageName);
        }

        $out->info("Linked [{$packageName}] from [{$repoPath}].");

        return 0;
    }

    public function add(
        string $packageName,
        string $qualifiedPath,
        ?string $userConstraint,
        bool $requireNotDev,
        bool $noUpdate,
        bool $noSymlink,
        ComposerLinkOutput $out,
    ): int {
        if ($this->composer->getAnyPackageRequirement($packageName) !== null) {
            $out->error(
                "Package [{$packageName}] is already required. "
                    . 'Use `composer link` (or composer-link:link) for a local path.'
            );

            return 1;
        }

        $inspected = $this->inspector->inspect($qualifiedPath);
        if ($inspected->name !== $packageName) {
            $out->error(sprintf(
                'Package name mismatch. Expected [%s] but local composer.json has [%s].',
                $packageName,
                $inspected->name
            ));

            return 1;
        }

        $active = $this->resolver->resolveForBootstrap($userConstraint);
        $section = $requireNotDev ? 'require' : 'require-dev';
        $symlink = ! $noSymlink && ($this->config['default_symlink'] ?? true);
        $repoPath = $this->composer->repositoryUrlForPackagePath($inspected->path);

        $this->store->put($packageName, [
            'path' => $repoPath,
            'mode' => 'bootstrap',
            'symlink' => $symlink,
            'original_constraint' => null,
            'active_constraint' => $active,
            'installed_as' => $section,
        ]);

        $this->composer->syncFromStore($this->store->all());
        $this->composer->setPackageRequirement($packageName, $active, $section);

        if (! $noUpdate) {
            $this->runner->updatePackage($packageName);
        }

        $out->info("Added [{$packageName}] from [{$repoPath}] to {$section}.");

        return 0;
    }

    public function unlink(
        string $packageName,
        bool $noUpdate,
        bool $remove,
        ComposerLinkOutput $out,
    ): int {
        $entry = $this->store->get($packageName);
        if ($entry === null) {
            $out->error(sprintf('No composer-link state found for [%s].', $packageName));

            return 1;
        }

        $mode = $entry['mode'];

        if ($mode === 'bootstrap') {
            if (! $remove) {
                $out->error(
                    'Bootstrap packages: use --remove, or `composer promote` to use a published version.'
                );

                return 1;
            }

            $this->store->forget($packageName);
            $this->composer->syncFromStore($this->store->all());
            $this->composer->removePackageRequirement($packageName);

            if (! $noUpdate) {
                $this->runner->runRaw(['update']);
            }

            $out->info(sprintf(
                'Removed [%s] from the local composer manifest and refreshed the lock file.',
                $packageName
            ));

            return 0;
        }

        $original = $entry['original_constraint'] ?? null;
        $section = $entry['installed_as'];

        $this->store->forget($packageName);
        $this->composer->syncFromStore($this->store->all());

        if ($original !== null && $original !== '') {
            $this->composer->setPackageRequirement($packageName, $original, $section);
        }

        if (! $noUpdate) {
            $this->runner->updatePackage($packageName);
        }

        $constraintLabel = ($original !== null && $original !== '') ? $original : '(previous)';
        $out->info(sprintf(
            'Unlinked [%s]; restored constraint to [%s] in %s.',
            $packageName,
            $constraintLabel,
            $section
        ));

        return 0;
    }

    public function promote(
        string $packageName,
        string $constraint,
        bool $noUpdate,
        ComposerLinkOutput $out,
    ): int {
        $entry = $this->store->get($packageName);
        if ($entry === null) {
            $out->error(sprintf('No composer-link state for [%s]. Nothing to promote.', $packageName));

            return 1;
        }

        $section = $entry['installed_as'];

        $this->store->forget($packageName);
        $this->composer->syncFromStore($this->store->all());
        $this->composer->setPackageRequirement($packageName, $constraint, $section);

        if (! $noUpdate) {
            $this->runner->updatePackage($packageName);
        }

        $out->info(sprintf(
            'Promoted [%s] to [%s] in %s (Packagist/VCS).',
            $packageName,
            $constraint,
            $section
        ));

        return 0;
    }

    public function status(ComposerLinkOutput $out): int
    {
        $packages = $this->store->all();
        if ($packages === []) {
            $out->info('No local package overrides (composer-link).');

            return 0;
        }

        $rows = [];
        foreach ($packages as $name => $entry) {
            $path = $entry['path'];
            $pathOk = PathHelper::pathExistsForProject($path, $this->projectRoot);
            $rows[] = [
                $name,
                $entry['mode'],
                $path,
                $entry['symlink'] ? 'yes' : 'no',
                $entry['active_constraint'],
                $pathOk ? 'ok' : 'missing',
            ];
        }

        $out->table(
            ['Package', 'Mode', 'Path', 'Symlink', 'Active constraint', 'Path status'],
            $rows
        );

        return 0;
    }

    public function refresh(bool $noUpdate, ComposerLinkOutput $out): int
    {
        $packages = $this->store->all();
        if ($packages === []) {
            $out->warn('No packages in ' . $this->overrideLabel() . ' — nothing to refresh.');
            $this->composer->syncFromStore([]);

            return 0;
        }

        $this->composer->syncFromStore($packages);

        if ($noUpdate) {
            $out->info('Repositories synced from ' . $this->overrideLabel() . ' (skipped composer update).');

            return 0;
        }

        $this->runner->updatePackages(array_keys($packages));
        $out->info('Refreshed ' . count($packages) . ' package(s).');

        return 0;
    }

    public function doctor(ComposerLinkOutput $out): int
    {
        $healthy = true;

        $overridePath = $this->projectRoot . DIRECTORY_SEPARATOR
            . ($this->config['overrides_file'] ?? 'packages-local.json');
        $basename = basename($overridePath);

        if (! is_file($overridePath)) {
            $out->line('○ Override file not present yet (expected at ' . $basename . ' when linking).');
        }

        if (! $this->doctorGitignore($out)) {
            $healthy = false;
        }
        if (! $this->doctorLinkedPaths($out)) {
            $healthy = false;
        }
        $this->doctorRepositoryCount($out);

        return $healthy ? 0 : 1;
    }

    private function doctorGitignore(ComposerLinkOutput $out): bool
    {
        $gitignore = $this->projectRoot . DIRECTORY_SEPARATOR . '.gitignore';
        $localJson = basename($this->config['local_composer_json'] ?? 'composer.local.json');
        $localLock = basename(
            ComposerJsonManager::lockPathForComposerJson(
                $this->projectRoot . DIRECTORY_SEPARATOR . $localJson
            )
        );
        $required = array_values(array_unique([
            basename($this->config['overrides_file'] ?? 'packages-local.json'),
            $localJson,
            $localLock,
        ]));

        if (! is_file($gitignore)) {
            $block = $this->gitignoreComposerLinkBlock($required);
            if (file_put_contents($gitignore, $block) === false) {
                $out->error('Could not create .gitignore at project root.');

                return false;
            }
            $out->info('✓ Created .gitignore with composer-link local-only entries.');

            return true;
        }

        $contents = (string) file_get_contents($gitignore);
        $missing = [];
        foreach ($required as $basename) {
            if (! $this->gitignoreMentionsBasename($contents, $basename)) {
                $missing[] = $basename;
            }
        }

        if ($missing !== []) {
            $append = "\n" . $this->gitignoreComposerLinkBlock($missing);
            $prefix = $contents !== '' && ! str_ends_with($contents, "\n") ? "\n" : '';
            if (file_put_contents($gitignore, $prefix . $append, FILE_APPEND | LOCK_EX) === false) {
                $out->error('Could not update .gitignore.');

                return false;
            }
            $out->info('✓ Appended to .gitignore (local-only): ' . implode(', ', $missing));

            return true;
        }

        $out->info('✓ Local-only files are listed in .gitignore.');

        return true;
    }

    /**
     * @param list<string> $basenames
     */
    private function gitignoreComposerLinkBlock(array $basenames): string
    {
        $lines = ['# composer-link (local-only — added/updated by composer link-doctor)'];
        foreach ($basenames as $basename) {
            $lines[] = '/' . ltrim($basename, '/');
        }

        return implode("\n", $lines) . "\n";
    }

    private function gitignoreMentionsBasename(string $contents, string $basename): bool
    {
        foreach (explode("\n", $contents) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $line = ltrim($line, '/');
            if ($line === $basename) {
                return true;
            }
        }

        return str_contains($contents, $basename);
    }

    private function doctorLinkedPaths(ComposerLinkOutput $out): bool
    {
        $healthy = true;
        foreach ($this->store->all() as $name => $entry) {
            $path = $entry['path'];
            if ($path === '') {
                $out->error(sprintf('Package [%s] has an empty path.', $name));
                $healthy = false;

                continue;
            }

            $resolved = str_starts_with($path, '/') || preg_match('#^[a-zA-Z]:[\\\\/]#', $path) === 1
                ? $path
                : $this->projectRoot . DIRECTORY_SEPARATOR . $path;

            if (! is_dir($resolved)) {
                $out->error(sprintf('Path missing for [%s]: %s', $name, $path));
                $healthy = false;

                continue;
            }

            $out->info(sprintf('✓ [%s] → %s', $name, $path));
        }

        return $healthy;
    }

    private function doctorRepositoryCount(ComposerLinkOutput $out): void
    {
        $label = basename($this->config['local_composer_json'] ?? 'composer.local.json');
        if (! $this->composer->workingManifestExists()) {
            $out->line(sprintf(
                'No %s yet — 0 composer-link path repositories (run `composer local-bootstrap` or use `link` / `add`).',
                $label
            ));

            return;
        }

        $data = $this->composer->read();
        $repos = $data['repositories'] ?? [];
        $marker = $this->config['repository_marker'] ?? 'composer-link';
        $managed = 0;
        if (! is_array($repos)) {
            $out->line(sprintf('%s has 0 composer-link path repository entries.', $label));

            return;
        }

        foreach ($repos as $repo) {
            if (! is_array($repo)) {
                continue;
            }
            $options = $repo['options'] ?? null;
            if (! is_array($options)) {
                continue;
            }
            if (isset($options[$marker]) && $options[$marker] === true) {
                ++$managed;
            }
        }

        $suffix = $managed === 1 ? 'y' : 'ies';
        $out->line(sprintf('%s has %d composer-link path repository entr%s.', $label, $managed, $suffix));
    }

    private function overrideLabel(): string
    {
        return basename($this->config['overrides_file'] ?? 'packages-local.json');
    }
}
