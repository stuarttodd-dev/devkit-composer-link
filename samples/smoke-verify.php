<?php

declare(strict_types=1);

/**
 * Quick check that `smoke/smoke-test-package` is installed and autoloaded (e.g. after `composer add` or `composer link`).
 *
 * Copy this file into your **main project** (the app that requires composer-link):
 *
 *   - Either the **project root** next to `vendor/`, or
 *   - A subfolder such as `samples/` (this script looks for `vendor/autoload.php` one level up).
 *
 * Then:
 *
 *   php smoke-verify.php
 *   # or: php samples/smoke-verify.php
 */

$autoload = null;
foreach ([__DIR__ . '/vendor/autoload.php', __DIR__ . '/../vendor/autoload.php'] as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}

if ($autoload === null) {
    fwrite(STDERR, "Could not find vendor/autoload.php. Run this from the project root (or from samples/ under the root) after `composer install` / `composer local-install`.\n");
    exit(1);
}

require $autoload;

use SmokeTest\SmokeMarker;

$out = SmokeMarker::ping();
echo $out . PHP_EOL;

if ($out === 'smoke-test-package') {
    echo "(Default string from the bundled smoke package.)\n";
} else {
    echo "(Custom string — you are loading a modified SmokeMarker from your path/symlink.)\n";
}
