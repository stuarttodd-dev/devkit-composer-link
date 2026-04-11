<?php

declare(strict_types=1);

use HalfShellStudios\ComposerLink\Services\ComposerJsonManager;
use HalfShellStudios\ComposerLink\Support\Json;

test('syncFromStore injects marked path repositories into local manifest', function (): void {
    $dir = sys_get_temp_dir() . '/cl-test-' . uniqid();
    mkdir($dir);
    $canonical = $dir . '/composer.json';
    $working = $dir . '/composer.local.json';
    Json::write($canonical, [
        'name' => 'test/project',
        'require' => ['foo/bar' => '^1.0'],
    ]);

    $pkgDir = $dir . '/packages/foo';
    mkdir($pkgDir, 0755, true);

    $manager = new ComposerJsonManager($canonical, $working, 'composer-link');
    $manager->syncFromStore([
        'foo/bar' => [
            'path' => $pkgDir,
            'symlink' => true,
        ],
    ]);

    expect(is_file($working))->toBeTrue();
    $data = Json::read($working);
    expect($data['repositories'])->toBeArray()
        ->and($data['repositories'][0]['type'])->toBe('path')
        ->and($data['repositories'][0]['options']['composer-link'])->toBeTrue();

    unlink($working);
    unlink($canonical);
    rmdir($pkgDir);
    rmdir(dirname($pkgDir));
    rmdir($dir);
});

test('lockPathForComposerJson strips json extension and appends lock', function (): void {
    expect(ComposerJsonManager::lockPathForComposerJson('/app/composer.local.json'))
        ->toBe('/app/composer.local.lock');
    expect(ComposerJsonManager::lockPathForComposerJson('/app/composer.json'))
        ->toBe('/app/composer.lock');
});

test('read returns canonical when working manifest missing', function (): void {
    $dir = sys_get_temp_dir() . '/cl-cjm-' . uniqid();
    mkdir($dir, 0755, true);
    $canonical = $dir . '/composer.json';
    $working = $dir . '/composer.local.json';
    Json::write($canonical, ['name' => 'a/b', 'require' => ['x/y' => '^1']]);

    $manager = new ComposerJsonManager($canonical, $working, 'composer-link');
    expect($manager->workingManifestExists())->toBeFalse()
        ->and($manager->read())->toBe(Json::read($canonical));

    unlink($canonical);
    rmdir($dir);
});

test('getCanonicalPackageRequirement reads committed manifest only', function (): void {
    $dir = sys_get_temp_dir() . '/cl-cjm-' . uniqid();
    mkdir($dir, 0755, true);
    $canonical = $dir . '/composer.json';
    $working = $dir . '/composer.local.json';
    Json::write($canonical, ['require' => ['only/here' => '^1']]);
    Json::write($working, ['require-dev' => ['other/pkg' => '@dev']]);

    $manager = new ComposerJsonManager($canonical, $working, 'composer-link');
    expect($manager->getCanonicalPackageRequirement('only/here'))->not->toBeNull()
        ->and($manager->getCanonicalPackageRequirement('other/pkg'))->toBeNull();

    unlink($working);
    unlink($canonical);
    rmdir($dir);
});

test('getAnyPackageRequirement finds package in working manifest', function (): void {
    $dir = sys_get_temp_dir() . '/cl-cjm-' . uniqid();
    mkdir($dir, 0755, true);
    $canonical = $dir . '/composer.json';
    $working = $dir . '/composer.local.json';
    Json::write($canonical, ['name' => 'app']);
    Json::write($working, ['require-dev' => ['boot/pkg' => '@dev']]);

    $manager = new ComposerJsonManager($canonical, $working, 'composer-link');
    $r = $manager->getAnyPackageRequirement('boot/pkg');
    expect($r)->not->toBeNull()
        ->and($r['section'])->toBe('require-dev');

    unlink($working);
    unlink($canonical);
    rmdir($dir);
});

test('bootstrapWorkingManifest refuses overwrite without force', function (): void {
    $dir = sys_get_temp_dir() . '/cl-cjm-' . uniqid();
    mkdir($dir, 0755, true);
    $canonical = $dir . '/composer.json';
    $working = $dir . '/composer.local.json';
    Json::write($canonical, ['name' => 'a']);
    Json::write($working, ['name' => 'b']);

    $manager = new ComposerJsonManager($canonical, $working, 'composer-link');
    $manager->bootstrapWorkingManifest(false);
})->throws(\InvalidArgumentException::class);

test('bootstrapWorkingManifest copies lock when present', function (): void {
    $dir = sys_get_temp_dir() . '/cl-cjm-' . uniqid();
    mkdir($dir, 0755, true);
    $canonical = $dir . '/composer.json';
    $working = $dir . '/composer.local.json';
    $lock = $dir . '/composer.lock';
    Json::write($canonical, ['name' => 'a']);
    file_put_contents($lock, '{"content-hash":"x"}');

    $manager = new ComposerJsonManager($canonical, $working, 'composer-link');
    $manager->bootstrapWorkingManifest(true);

    expect(is_file(ComposerJsonManager::lockPathForComposerJson($working)))->toBeTrue();

    unlink(ComposerJsonManager::lockPathForComposerJson($working));
    unlink($working);
    unlink($canonical);
    unlink($lock);
    rmdir($dir);
});

