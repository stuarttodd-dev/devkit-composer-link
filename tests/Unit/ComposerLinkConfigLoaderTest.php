<?php

declare(strict_types=1);

use HalfShellStudios\ComposerLink\Config\ComposerLinkConfigLoader;
use HalfShellStudios\ComposerLink\Support\Json;

test('defaultConfig includes expected keys', function (): void {
    $d = ComposerLinkConfigLoader::defaultConfig();
    expect($d)->toHaveKey('overrides_file')
        ->and($d)->toHaveKey('local_composer_json')
        ->and($d)->toHaveKey('composer_json')
        ->and($d)->toHaveKey('repository_marker')
        ->and($d['local_composer_json'])->toBeString();
});

test('loadForProject returns defaults when composer.json missing', function (): void {
    $dir = sys_get_temp_dir() . '/cl-cfg-' . uniqid();
    mkdir($dir, 0755, true);
    $loaded = ComposerLinkConfigLoader::loadForProject($dir);
    expect($loaded['overrides_file'])->toBe(ComposerLinkConfigLoader::defaultConfig()['overrides_file']);
    rmdir($dir);
});

test('loadForProject merges extra.composer-link string keys', function (): void {
    $dir = sys_get_temp_dir() . '/cl-cfg-' . uniqid();
    mkdir($dir, 0755, true);
    Json::write($dir . '/composer.json', [
        'name' => 'x/y',
        'extra' => [
            'composer-link' => [
                'overrides_file' => 'my-overrides.json',
                'local_composer_json' => 'my.local.json',
                'repository_marker' => 'my-marker',
                'default_symlink' => false,
                'update_with_all_dependencies' => false,
            ],
        ],
    ]);

    $loaded = ComposerLinkConfigLoader::loadForProject($dir);
    expect($loaded['overrides_file'])->toBe('my-overrides.json')
        ->and($loaded['local_composer_json'])->toBe('my.local.json')
        ->and($loaded['repository_marker'])->toBe('my-marker')
        ->and($loaded['default_symlink'])->toBeFalse()
        ->and($loaded['update_with_all_dependencies'])->toBeFalse();

    unlink($dir . '/composer.json');
    rmdir($dir);
});

test('loadForProject ignores unknown extra keys', function (): void {
    $dir = sys_get_temp_dir() . '/cl-cfg-' . uniqid();
    mkdir($dir, 0755, true);
    Json::write($dir . '/composer.json', [
        'name' => 'x/y',
        'extra' => [
            'composer-link' => [
                'unknown' => 'ignored',
            ],
        ],
    ]);

    $loaded = ComposerLinkConfigLoader::loadForProject($dir);
    expect($loaded)->not->toHaveKey('unknown');

    unlink($dir . '/composer.json');
    rmdir($dir);
});
