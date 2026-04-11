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
