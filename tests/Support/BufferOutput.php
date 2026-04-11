<?php

declare(strict_types=1);

namespace Tests\Support;

use HalfShellStudios\ComposerLink\Contracts\ComposerLinkOutput;

/**
 * Captures composer-link task output for assertions.
 */
final class BufferOutput implements ComposerLinkOutput
{
    /** @var list<string> */
    public array $infos = [];

    /** @var list<string> */
    public array $errors = [];

    /** @var list<string> */
    public array $warnings = [];

    /** @var list<string> */
    public array $lines = [];

    /** @var list<array{0: list<string>, 1: list<list<string>>}> */
    public array $tables = [];

    #[\Override]
    public function info(string $message): void
    {
        $this->infos[] = $message;
    }

    #[\Override]
    public function error(string $message): void
    {
        $this->errors[] = $message;
    }

    #[\Override]
    public function warn(string $message): void
    {
        $this->warnings[] = $message;
    }

    #[\Override]
    public function line(string $message): void
    {
        $this->lines[] = $message;
    }

    /**
     * @param list<string> $headers
     * @param list<list<string>> $rows
     */
    #[\Override]
    public function table(array $headers, array $rows): void
    {
        $this->tables[] = [$headers, $rows];
    }
}
