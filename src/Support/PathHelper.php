<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Support;

final class PathHelper
{
    public static function qualifyPackagePath(string $path, string $projectRoot): string
    {
        $path = trim($path);
        if ($path === '') {
            return $path;
        }

        if (str_starts_with($path, '/') || self::isWindowsAbsolute($path)) {
            return $path;
        }

        return rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
    }

    public static function pathExistsForProject(string $path, string $projectRoot): bool
    {
        if ($path === '') {
            return false;
        }

        if (str_starts_with($path, '/') || preg_match('#^[a-zA-Z]:[\\\\/]#', $path) === 1) {
            return is_dir($path);
        }

        $joined = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;

        return is_dir($joined);
    }

    private static function isWindowsAbsolute(string $path): bool
    {
        $sep = $path[2] ?? '';

        return strlen($path) >= 3 && ctype_alpha($path[0]) && $path[1] === ':'
            && ($sep === '\\' || $sep === '/');
    }
}
