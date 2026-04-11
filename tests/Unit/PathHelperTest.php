<?php

declare(strict_types=1);

use HalfShellStudios\ComposerLink\Support\PathHelper;

test('qualifyPackagePath returns empty for empty string', function (): void {
    expect(PathHelper::qualifyPackagePath('', '/project'))->toBe('');
});

test('qualifyPackagePath keeps unix absolute paths', function (): void {
    expect(PathHelper::qualifyPackagePath('/abs/pkg', '/project'))->toBe('/abs/pkg');
});

test('qualifyPackagePath keeps windows absolute paths', function (): void {
    expect(PathHelper::qualifyPackagePath('C:\\dev\\pkg', '/project'))->toBe('C:\\dev\\pkg');
    expect(PathHelper::qualifyPackagePath('D:/dev/pkg', '/project'))->toBe('D:/dev/pkg');
});

test('qualifyPackagePath joins relative to project root', function (): void {
    $root = '/var/app';
    expect(PathHelper::qualifyPackagePath('lib/foo', $root))->toBe($root . DIRECTORY_SEPARATOR . 'lib/foo');
});

test('pathExistsForProject returns false for empty path', function (): void {
    expect(PathHelper::pathExistsForProject('', '/tmp'))->toBeFalse();
});

test('pathExistsForProject resolves relative under project root', function (): void {
    $dir = sys_get_temp_dir() . '/cl-ph-' . uniqid();
    mkdir($dir . '/sub', 0755, true);
    expect(PathHelper::pathExistsForProject('sub', $dir))->toBeTrue();
    rmdir($dir . '/sub');
    rmdir($dir);
});

test('pathExistsForProject accepts absolute directory', function (): void {
    expect(PathHelper::pathExistsForProject(sys_get_temp_dir(), '/tmp'))->toBeTrue();
});
