<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Services;

use HalfShellStudios\ComposerLink\Dto\InspectedPackage;
use HalfShellStudios\ComposerLink\Support\Json;
use RuntimeException;

final class PackageInspector
{
    public function inspect(string $path): InspectedPackage
    {
        $absolute = $this->resolveExistingPath($path);
        $composerPath = $absolute . DIRECTORY_SEPARATOR . 'composer.json';
        if (! is_file($composerPath)) {
            throw new RuntimeException("No composer.json found at {$composerPath}");
        }

        $data = Json::read($composerPath);
        $name = $data['name'] ?? null;
        if (! is_string($name) || $name === '') {
            throw new RuntimeException("Missing or invalid \"name\" in {$composerPath}");
        }

        $version = isset($data['version']) && is_string($data['version']) ? $data['version'] : null;

        return new InspectedPackage($name, $version, $absolute);
    }

    private function resolveExistingPath(string $path): string
    {
        $resolved = realpath($path);
        if ($resolved === false || ! is_dir($resolved)) {
            throw new RuntimeException("Path does not exist or is not a directory: {$path}");
        }

        return $resolved;
    }
}
