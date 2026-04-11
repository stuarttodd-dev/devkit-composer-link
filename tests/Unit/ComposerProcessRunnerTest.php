<?php

declare(strict_types=1);

use HalfShellStudios\ComposerLink\Services\ComposerProcessRunner;

test('runRaw passes COMPOSER to child process when set', function (): void {
    $dir = sys_get_temp_dir();
    $marker = tempnam($dir, 'clcomp');
    if ($marker === false) {
        throw new RuntimeException('tempnam failed');
    }

    $runner = new ComposerProcessRunner($dir, PHP_BINARY, false, $marker);
    $code = $runner->runRaw([
        '-r',
        'exit(getenv("COMPOSER") === ' . var_export($marker, true) . ' ? 0 : 1);',
    ]);
    expect($code)->toBe(0);
    unlink($marker);
});

test('runRaw works without COMPOSER override', function (): void {
    $runner = new ComposerProcessRunner(sys_get_temp_dir(), PHP_BINARY, false, null);
    $code = $runner->runRaw(['-r', 'exit(0);']);
    expect($code)->toBe(0);
});
