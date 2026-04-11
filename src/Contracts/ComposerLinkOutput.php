<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Contracts;

interface ComposerLinkOutput
{
    public function info(string $message): void;

    public function error(string $message): void;

    public function warn(string $message): void;

    public function line(string $message): void;

    /**
     * @param list<string> $headers
     * @param list<list<string>> $rows
     */
    public function table(array $headers, array $rows): void;
}
