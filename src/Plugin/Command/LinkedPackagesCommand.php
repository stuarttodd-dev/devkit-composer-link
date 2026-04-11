<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Plugin\Command;

use HalfShellStudios\ComposerLink\Output\SymfonyStyleOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class LinkedPackagesCommand extends AbstractComposerLinkCommand
{
    #[\Override]
    protected function configure(): void
    {
        $this->setName('linked');
        $this->setDescription('List packages managed by composer-link (not the same as `composer status`)');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $io = new SymfonyStyleOutput($style);

        return $this->tasks()->status($io);
    }
}
