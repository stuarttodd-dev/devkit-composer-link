<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Dto;

final class InspectedPackage
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $version,
        public readonly string $path,
    ) {
    }
}
