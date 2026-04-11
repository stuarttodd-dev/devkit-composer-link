<?php

declare(strict_types=1);

use HalfShellStudios\ComposerLink\Services\PackageInspector;
use HalfShellStudios\ComposerLink\Support\Json;

test('inspect returns InspectedPackage', function (): void {
    $dir = sys_get_temp_dir() . '/cl-insp-' . uniqid();
    mkdir($dir, 0755, true);
    Json::write($dir . '/composer.json', [
        'name' => 'vendor/pkg',
        'version' => '2.0.0',
    ]);

    $pkg = (new PackageInspector())->inspect($dir);
    expect($pkg->name)->toBe('vendor/pkg')
        ->and($pkg->version)->toBe('2.0.0')
        ->and($pkg->path)->toBe(realpath($dir));

    unlink($dir . '/composer.json');
    rmdir($dir);
});

test('inspect allows missing version', function (): void {
    $dir = sys_get_temp_dir() . '/cl-insp-' . uniqid();
    mkdir($dir, 0755, true);
    Json::write($dir . '/composer.json', ['name' => 'a/b']);

    $pkg = (new PackageInspector())->inspect($dir);
    expect($pkg->version)->toBeNull();

    unlink($dir . '/composer.json');
    rmdir($dir);
});

test('inspect throws when composer.json missing', function (): void {
    $dir = sys_get_temp_dir() . '/cl-insp-' . uniqid();
    mkdir($dir, 0755, true);
    (new PackageInspector())->inspect($dir);
    rmdir($dir);
})->throws(RuntimeException::class);

test('inspect throws when name invalid', function (): void {
    $dir = sys_get_temp_dir() . '/cl-insp-' . uniqid();
    mkdir($dir, 0755, true);
    Json::write($dir . '/composer.json', ['name' => '']);

    (new PackageInspector())->inspect($dir);
    unlink($dir . '/composer.json');
    rmdir($dir);
})->throws(RuntimeException::class);

test('inspect throws when path not a directory', function (): void {
    (new PackageInspector())->inspect(sys_get_temp_dir() . '/nope-' . uniqid());
})->throws(RuntimeException::class);
