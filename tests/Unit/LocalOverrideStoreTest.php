<?php

declare(strict_types=1);

use HalfShellStudios\ComposerLink\Services\LocalOverrideStore;

test('reads legacy composer.local-packages.json when packages-local is empty', function (): void {
    $dir = sys_get_temp_dir() . '/cl-test-' . uniqid('store', true);
    mkdir($dir, 0755, true);
    $primary = $dir . '/packages-local.json';
    file_put_contents($dir . '/composer.local-packages.json', json_encode([
        'packages' => [
            'a/b' => [
                'path' => 'lib/ab',
                'mode' => 'override',
                'symlink' => true,
                'original_constraint' => '^1.0',
                'active_constraint' => '^1.0',
                'installed_as' => 'require',
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    $store = new LocalOverrideStore($primary);
    $all = $store->all();
    expect($all)->toHaveKey('a/b');
    expect($all['a/b']['path'])->toBe('lib/ab');
});

test('writing to packages-local removes legacy file', function (): void {
    $dir = sys_get_temp_dir() . '/cl-test-' . uniqid('store', true);
    mkdir($dir, 0755, true);
    $primary = $dir . '/packages-local.json';
    file_put_contents($dir . '/composer.local-packages.json', json_encode([
        'packages' => [
            'a/b' => [
                'path' => 'lib/ab',
                'mode' => 'override',
                'symlink' => true,
                'original_constraint' => '^1.0',
                'active_constraint' => '^1.0',
                'installed_as' => 'require',
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    $store = new LocalOverrideStore($primary);
    $store->put('x/y', [
        'path' => 'lib/xy',
        'mode' => 'override',
        'symlink' => true,
        'original_constraint' => '^2.0',
        'active_constraint' => '^2.0',
        'installed_as' => 'require-dev',
    ]);

    expect(is_file($primary))->toBeTrue();
    expect(is_file($dir . '/composer.local-packages.json'))->toBeFalse();
});

test('get forget and replaceAll', function (): void {
    $dir = sys_get_temp_dir() . '/cl-store-' . uniqid();
    mkdir($dir, 0755, true);
    $primary = $dir . '/packages-local.json';

    $store = new LocalOverrideStore($primary);
    $store->put('a/b', [
        'path' => 'lib/ab',
        'mode' => 'override',
        'symlink' => true,
        'original_constraint' => '^1.0',
        'active_constraint' => '^1.0',
        'installed_as' => 'require',
    ]);
    expect($store->get('a/b'))->not->toBeNull();

    $store->forget('a/b');
    expect($store->get('a/b'))->toBeNull()
        ->and(is_file($primary))->toBeFalse();

    $store->put('x/y', [
        'path' => 'lib/xy',
        'mode' => 'bootstrap',
        'symlink' => false,
        'original_constraint' => null,
        'active_constraint' => '@dev',
        'installed_as' => 'require-dev',
    ]);
    $store->replaceAll([]);
    expect($store->all())->toBe([])
        ->and(is_file($primary))->toBeFalse();
});
