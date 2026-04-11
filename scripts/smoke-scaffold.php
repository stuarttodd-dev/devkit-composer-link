<?php

declare(strict_types=1);

/**
 * Writes smoke/test-package/ under the repository root for manual QA of composer-link.
 *
 * Run: php scripts/smoke-scaffold.php
 * Or:  composer smoke:scaffold
 */

$root = dirname(__DIR__);
$base = $root . DIRECTORY_SEPARATOR . 'smoke' . DIRECTORY_SEPARATOR . 'test-package';

$files = [
    'composer.json' => <<<'JSON'
{
    "name": "smoke/smoke-test-package",
    "description": "Dummy package for manually testing composer-link (see main README, QA section).",
    "type": "library",
    "version": "1.0.0",
    "license": "MIT",
    "require": {
        "php": "^8.3"
    },
    "autoload": {
        "psr-4": {
            "SmokeTest\\": "src/"
        }
    }
}

JSON
    ,
    'src/SmokeMarker.php' => <<<'PHP'
<?php

declare(strict_types=1);

namespace SmokeTest;

final class SmokeMarker
{
    public static function ping(): string
    {
        return 'smoke-test-package';
    }
}

PHP
    ,
];

foreach ($files as $relative => $contents) {
    $path = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $dir = dirname($path);
    if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
        fwrite(STDERR, "Could not create directory: {$dir}\n");
        exit(1);
    }
    if (file_put_contents($path, $contents) === false) {
        fwrite(STDERR, "Could not write: {$path}\n");
        exit(1);
    }
}

fwrite(STDOUT, "Scaffolded: {$base}\n");
