<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Plugin\Command;

use HalfShellStudios\ComposerLink\Output\SymfonyStyleOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class DoctorLinkCommand extends AbstractComposerLinkCommand
{
    #[\Override]
    protected function configure(): void
    {
        $this->setName('link-doctor');
        $this->setDescription('Check composer-link setup (gitignore, paths)');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $io = new SymfonyStyleOutput($style);

        return $this->tasks()->doctor($io);
    }
}
