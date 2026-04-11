<?php

declare(strict_types=1);

use HalfShellStudios\ComposerLink\ComposerLinkFactory;
use HalfShellStudios\ComposerLink\Support\Json;
use Tests\Support\BufferOutput;

function projectFixture(callable $fn): void
{
    $dir = sys_get_temp_dir() . '/cl-task-' . uniqid();
    mkdir($dir, 0755, true);
    try {
        $fn($dir);
    } finally {
        $clean = function (string $path) use (&$clean): void {
            if (is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                foreach (scandir($path) ?: [] as $f) {
                    if ($f === '.' || $f === '..') {
                        continue;
                    }
                    $clean($path . DIRECTORY_SEPARATOR . $f);
                }
                rmdir($path);
            }
        };
        if (is_dir($dir)) {
            $clean($dir);
        }
    }
}

test('link writes packages-local and composer.local with no-update', function (): void {
    projectFixture(function (string $dir): void {
        $pkg = $dir . '/pkg';
        mkdir($pkg, 0755, true);
        Json::write($dir . '/composer.json', [
            'name' => 'app/app',
            'require' => ['t/linked' => '^1.0'],
        ]);
        Json::write($pkg . '/composer.json', [
            'name' => 't/linked',
            'version' => '1.2.0',
        ]);

        $tasks = ComposerLinkFactory::createWithConfig($dir, []);
        $out = new BufferOutput();
        $code = $tasks->link('t/linked', $pkg, null, true, false, $out);

        expect($code)->toBe(0)
            ->and($out->errors)->toBe([])
            ->and(is_file($dir . '/packages-local.json'))->toBeTrue()
            ->and(is_file($dir . '/composer.local.json'))->toBeTrue();

        $local = Json::read($dir . '/composer.local.json');
        expect($local['repositories'][0]['type'] ?? null)->toBe('path');
    });
});

test('link fails when package not in canonical composer.json', function (): void {
    projectFixture(function (string $dir): void {
        Json::write($dir . '/composer.json', ['name' => 'app/app', 'require' => []]);
        $pkg = $dir . '/pkg';
        mkdir($pkg, 0755, true);
        Json::write($pkg . '/composer.json', ['name' => 't/miss', 'version' => '1.0.0']);

        $tasks = ComposerLinkFactory::createWithConfig($dir, []);
        $out = new BufferOutput();
        $code = $tasks->link('t/miss', $pkg, null, true, false, $out);

        expect($code)->toBe(1)->and($out->errors)->not->toBeEmpty();
    });
});

test('add bootstrap writes require-dev entry with no-update', function (): void {
    projectFixture(function (string $dir): void {
        $pkg = $dir . '/newp';
        mkdir($pkg, 0755, true);
        Json::write($dir . '/composer.json', ['name' => 'app/app', 'require' => ['php' => '^8.3']]);
        Json::write($pkg . '/composer.json', ['name' => 't/newp', 'version' => '0.1.0']);

        $tasks = ComposerLinkFactory::createWithConfig($dir, []);
        $out = new BufferOutput();
        $code = $tasks->add('t/newp', $pkg, null, false, true, false, $out);

        expect($code)->toBe(0)->and($out->errors)->toBe([]);
        $local = Json::read($dir . '/composer.local.json');
        expect($local['require-dev']['t/newp'] ?? null)->toBe('@dev');
    });
});

test('unlink restore override constraint with no-update', function (): void {
    projectFixture(function (string $dir): void {
        $pkg = $dir . '/pkg';
        mkdir($pkg, 0755, true);
        Json::write($dir . '/composer.json', [
            'name' => 'app/app',
            'require' => ['t/x' => '^2.0'],
        ]);
        Json::write($pkg . '/composer.json', ['name' => 't/x', 'version' => '2.1.0']);

        $tasks = ComposerLinkFactory::createWithConfig($dir, []);
        $out = new BufferOutput();
        expect($tasks->link('t/x', $pkg, null, true, false, $out))->toBe(0);

        $out2 = new BufferOutput();
        expect($tasks->unlink('t/x', true, false, $out2))->toBe(0);

        $local = Json::read($dir . '/composer.local.json');
        expect($local['require']['t/x'] ?? null)->toBe('^2.0');
    });
});

test('linked lists packages in table', function (): void {
    projectFixture(function (string $dir): void {
        $pkg = $dir . '/pkg';
        mkdir($pkg, 0755, true);
        Json::write($dir . '/composer.json', ['name' => 'a', 'require' => ['t/y' => '^1']]);
        Json::write($pkg . '/composer.json', ['name' => 't/y', 'version' => '1.0.0']);

        $tasks = ComposerLinkFactory::createWithConfig($dir, []);
        $out = new BufferOutput();
        $tasks->link('t/y', $pkg, null, true, false, $out);

        $out3 = new BufferOutput();
        expect($tasks->status($out3))->toBe(0)->and($out3->tables)->not->toBeEmpty();
    });
});

test('refresh with no-update syncs repositories', function (): void {
    projectFixture(function (string $dir): void {
        $pkg = $dir . '/pkg';
        mkdir($pkg, 0755, true);
        Json::write($dir . '/composer.json', ['name' => 'a', 'require' => ['t/z' => '^1']]);
        Json::write($pkg . '/composer.json', ['name' => 't/z', 'version' => '1.0.0']);

        $tasks = ComposerLinkFactory::createWithConfig($dir, []);
        $tasks->link('t/z', $pkg, null, true, false, new BufferOutput());

        $out = new BufferOutput();
        expect($tasks->refresh(true, $out))->toBe(0);
        $local = Json::read($dir . '/composer.local.json');
        expect($local['repositories'][0]['type'] ?? null)->toBe('path');
    });
});

test('doctor creates gitignore when missing', function (): void {
    projectFixture(function (string $dir): void {
        Json::write($dir . '/composer.json', ['name' => 'a']);

        $tasks = ComposerLinkFactory::createWithConfig($dir, []);
        $out = new BufferOutput();
        expect($tasks->doctor($out))->toBe(0);
        expect(is_file($dir . '/.gitignore'))->toBeTrue();
        $gi = (string) file_get_contents($dir . '/.gitignore');
        expect($gi)->toContain('packages-local.json')
            ->and($gi)->toContain('composer.local.json');
    });
});

test('promote sets published constraint with no-update', function (): void {
    projectFixture(function (string $dir): void {
        $pkg = $dir . '/pkg';
        mkdir($pkg, 0755, true);
        Json::write($dir . '/composer.json', ['name' => 'a', 'require' => ['t/prom' => '^1']]);
        Json::write($pkg . '/composer.json', ['name' => 't/prom', 'version' => '1.0.0']);

        $tasks = ComposerLinkFactory::createWithConfig($dir, []);
        expect($tasks->link('t/prom', $pkg, null, true, false, new BufferOutput()))->toBe(0);

        $out = new BufferOutput();
        expect($tasks->promote('t/prom', '^2.0', true, $out))->toBe(0)->and($out->errors)->toBe([]);

        $local = Json::read($dir . '/composer.local.json');
        expect($local['require']['t/prom'] ?? null)->toBe('^2.0');
    });
});
