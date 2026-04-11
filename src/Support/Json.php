<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Support;

use JsonException;
use RuntimeException;

final class Json
{
    /**
     * @return array<string, mixed>
     */
    public static function read(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return [];
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("Invalid JSON in {$path}: " . $e->getMessage(), 0, $e);
        }

        if (! is_array($data)) {
            throw new RuntimeException("Invalid JSON structure in {$path}");
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function write(string $path, array $data): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $encoded = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ) . "\n";

        $temp = $path . '.' . uniqid('tmp', true);
        if (file_put_contents($temp, $encoded) === false) {
            throw new RuntimeException("Could not write temporary file for {$path}");
        }

        if (! rename($temp, $path)) {
            if (is_file($temp)) {
                unlink($temp);
            }
            throw new RuntimeException("Could not write JSON file {$path}");
        }
    }
}
