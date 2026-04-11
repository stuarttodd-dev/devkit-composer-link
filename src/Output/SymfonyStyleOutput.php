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

    #[\Override]
    public function info(string $message): void
    {
        $this->style->text($message);
    }

    #[\Override]
    public function error(string $message): void
    {
        $this->style->error($message);
    }

    #[\Override]
    public function warn(string $message): void
    {
        $this->style->warning($message);
    }

    #[\Override]
    public function line(string $message): void
    {
        $this->style->writeln($message);
    }

    #[\Override]
    public function table(array $headers, array $rows): void
    {
        $this->style->table($headers, $rows);
    }
}
