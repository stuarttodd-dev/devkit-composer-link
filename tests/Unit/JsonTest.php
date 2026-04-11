<?php

declare(strict_types=1);

use HalfShellStudios\ComposerLink\Support\Json;

test('read returns empty array for missing file', function (): void {
    expect(Json::read(sys_get_temp_dir() . '/no-such-json-' . uniqid() . '.json'))->toBe([]);
});

test('read returns empty array for empty file', function (): void {
    $p = sys_get_temp_dir() . '/cl-json-' . uniqid() . '.json';
    file_put_contents($p, '');
    expect(Json::read($p))->toBe([]);
    unlink($p);
});

test('read decodes valid json', function (): void {
    $p = sys_get_temp_dir() . '/cl-json-' . uniqid() . '.json';
    file_put_contents($p, '{"a":1}');
    expect(Json::read($p))->toBe(['a' => 1]);
    unlink($p);
});

test('read throws on invalid json', function (): void {
    $p = sys_get_temp_dir() . '/cl-json-' . uniqid() . '.json';
    file_put_contents($p, '{');
    Json::read($p);
    unlink($p);
})->throws(\RuntimeException::class);

test('read throws when root is not an object', function (): void {
    $p = sys_get_temp_dir() . '/cl-json-' . uniqid() . '.json';
    file_put_contents($p, '"scalar"');
    Json::read($p);
    unlink($p);
})->throws(\RuntimeException::class, 'Invalid JSON structure');

test('write creates parent directory and round-trips', function (): void {
    $dir = sys_get_temp_dir() . '/cl-json-nested-' . uniqid();
    $p = $dir . '/deep/file.json';
    Json::write($p, ['x' => 'y']);
    expect(is_file($p))->toBeTrue()
        ->and(Json::read($p))->toBe(['x' => 'y']);
    unlink($p);
    rmdir(dirname($p));
    rmdir($dir);
});
