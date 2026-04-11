<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink;

use HalfShellStudios\ComposerLink\Config\ComposerLinkConfigLoader;
use HalfShellStudios\ComposerLink\Services\ComposerJsonManager;
use HalfShellStudios\ComposerLink\Services\ComposerProcessRunner;
use HalfShellStudios\ComposerLink\Services\ConstraintResolver;
use HalfShellStudios\ComposerLink\Services\LocalOverrideStore;
use HalfShellStudios\ComposerLink\Services\PackageInspector;
use InvalidArgumentException;

final class ComposerLinkFactory
{
    public static function createFromProjectRoot(string $projectRoot): ComposerLinkTasks
    {
        $resolved = self::resolveProjectRoot($projectRoot);

        /** @var array{overrides_file: string, composer_json: string, local_composer_json: string, composer_binary: string, repository_marker: string, default_symlink: bool, update_with_all_dependencies: bool} $config */
        $config = ComposerLinkConfigLoader::loadForProject($resolved);

        return self::buildTasks($resolved, $config);
    }

    /**
     * @param array<string, mixed> $config merged keys (e.g. from extra.composer-link)
     */
    public static function createWithConfig(string $projectRoot, array $config): ComposerLinkTasks
    {
        $resolved = self::resolveProjectRoot($projectRoot);
        $defaults = ComposerLinkConfigLoader::defaultConfig();
        /** @var array{overrides_file: string, composer_json: string, local_composer_json: string, composer_binary: string, repository_marker: string, default_symlink: bool, update_with_all_dependencies: bool} $merged */
        $merged = array_merge($defaults, $config);

        return self::buildTasks($resolved, $merged);
    }

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
    private static function buildTasks(string $resolved, array $config): ComposerLinkTasks
    {
        $store = new LocalOverrideStore($resolved . DIRECTORY_SEPARATOR . $config['overrides_file']);
        $canonical = $resolved . DIRECTORY_SEPARATOR . $config['composer_json'];
        $working = $resolved . DIRECTORY_SEPARATOR . $config['local_composer_json'];
        $composer = new ComposerJsonManager($canonical, $working, $config['repository_marker']);
        $runner = new ComposerProcessRunner(
            $resolved,
            $config['composer_binary'],
            $config['update_with_all_dependencies'],
            $working,
        );

        return new ComposerLinkTasks(
            $store,
            $composer,
            $runner,
            new PackageInspector(),
            new ConstraintResolver(),
            $config,
            $resolved,
        );
    }

    private static function resolveProjectRoot(string $projectRoot): string
    {
        $resolved = realpath($projectRoot);
        if ($resolved === false || ! is_dir($resolved)) {
            throw new InvalidArgumentException("Invalid project root: {$projectRoot}");
        }

        $composerJson = $resolved . DIRECTORY_SEPARATOR . 'composer.json';
        if (! is_file($composerJson)) {
            throw new InvalidArgumentException("No composer.json in {$resolved}");
        }

        return $resolved;
    }
}
