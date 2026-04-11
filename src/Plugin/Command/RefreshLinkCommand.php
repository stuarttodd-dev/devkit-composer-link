<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Plugin\Command;

use HalfShellStudios\ComposerLink\Output\SymfonyStyleOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class RefreshLinkCommand extends AbstractComposerLinkCommand
{
    protected function configure(): void
    {
        $this->setName('refresh');
        $this->setDescription('Rebuild path repositories from the packages-local override file');
        $this->addOption(
            'no-update',
            null,
            InputOption::VALUE_NONE,
            'Only rewrite composer.local.json, skip composer update'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $io = new SymfonyStyleOutput($style);

        return $this->tasks()->refresh((bool) $input->getOption('no-update'), $io);
    }
}
