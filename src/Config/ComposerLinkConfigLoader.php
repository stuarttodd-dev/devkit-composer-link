<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Config;

use HalfShellStudios\ComposerLink\Support\Json;

final class ComposerLinkConfigLoader
{
    /**
     * @return array<string, mixed>
     */
    public static function defaultConfig(): array
    {
        return self::loadDefaultsFromFile();
    }

    /**
     * @return array<string, mixed>
     */
    public static function loadForProject(string $projectRoot): array
    {
        $defaults = self::loadDefaultsFromFile();
        $composerPath = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.json';
        if (! is_file($composerPath)) {
            return $defaults;
        }

        $data = Json::read($composerPath);
        $extraRoot = $data['extra'] ?? null;
        if (! is_array($extraRoot)) {
            return $defaults;
        }

        $extra = $extraRoot['composer-link'] ?? null;
        if (! is_array($extra)) {
            return $defaults;
        }

        return self::merge($defaults, $extra);
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private static function merge(array $defaults, array $extra): array
    {
        $out = $defaults;
        foreach ($extra as $key => $value) {
            if (! array_key_exists($key, $defaults)) {
                continue;
            }
            if (is_bool($defaults[$key])) {
                $out[$key] = (bool) $value;

                continue;
            }
            if (is_string($defaults[$key]) && is_string($value)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadDefaultsFromFile(): array
    {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'composer-link.php';

        /** @var array<string, mixed> $config */
        $config = require $path;

        return $config;
    }
}
