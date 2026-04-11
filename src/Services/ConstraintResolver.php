<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Services;

use Composer\Semver\Semver;
use HalfShellStudios\ComposerLink\Dto\InspectedPackage;

final class ConstraintResolver
{
    /**
     * Decide which constraint to use when linking an existing dependency to a local path.
     */
    public function resolveForLink(
        string $originalConstraint,
        InspectedPackage $package,
        ?string $userConstraint,
    ): string {
        if ($userConstraint !== null && $userConstraint !== '') {
            return $userConstraint;
        }

        if ($package->version === null || $package->version === '') {
            return '@dev';
        }

        $normalized = $this->normalizeVersion($package->version);
        if ($this->looksLikeDevVersion($normalized)) {
            return '@dev';
        }

        try {
            if (Semver::satisfies($normalized, $originalConstraint)) {
                return $originalConstraint;
            }
        } catch (\Throwable) {
            // Fall through to @dev
        }

        return '@dev';
    }

    /**
     * Default constraint when adding a brand-new local package.
     */
    public function resolveForBootstrap(?string $userConstraint): string
    {
        if ($userConstraint !== null && $userConstraint !== '') {
            return $userConstraint;
        }

        return '@dev';
    }

    private function normalizeVersion(string $version): string
    {
        $trimmed = trim($version);
        if (str_starts_with($trimmed, 'v') && preg_match('/^v\d/', $trimmed) === 1) {
            return substr($trimmed, 1);
        }

        return $trimmed;
    }

    private function looksLikeDevVersion(string $normalized): bool
    {
        if (preg_match('/\b(dev|alpha|beta|RC|rc)\b/', $normalized) === 1) {
            return true;
        }

        return str_contains($normalized, 'dev-');
    }
}
