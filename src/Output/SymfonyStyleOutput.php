<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Output;

use HalfShellStudios\ComposerLink\Contracts\ComposerLinkOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

final class SymfonyStyleOutput implements ComposerLinkOutput
{
    public function __construct(
        private readonly SymfonyStyle $style,
    ) {
    }

    public function info(string $message): void
    {
        $this->style->text($message);
    }

    public function error(string $message): void
    {
        $this->style->error($message);
    }

    public function warn(string $message): void
    {
        $this->style->warning($message);
    }

    public function line(string $message): void
    {
        $this->style->writeln($message);
    }

    public function table(array $headers, array $rows): void
    {
        $this->style->table($headers, $rows);
    }
}
