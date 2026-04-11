<?php

declare(strict_types=1);

use HalfShellStudios\ComposerLink\ComposerLinkFactory;

test('createWithConfig throws for invalid project root', function (): void {
    ComposerLinkFactory::createWithConfig('/no/such/path/' . uniqid(), []);
})->throws(\InvalidArgumentException::class);

test('createWithConfig throws when composer.json missing', function (): void {
    $dir = sys_get_temp_dir() . '/cl-fac-' . uniqid();
    mkdir($dir, 0755, true);
    try {
        ComposerLinkFactory::createWithConfig($dir, []);
    } finally {
        rmdir($dir);
    }
})->throws(\InvalidArgumentException::class);
