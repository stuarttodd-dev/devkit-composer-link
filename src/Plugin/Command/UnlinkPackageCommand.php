<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Plugin\Command;

use HalfShellStudios\ComposerLink\Output\SymfonyStyleOutput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class UnlinkPackageCommand extends AbstractComposerLinkCommand
{
    protected function configure(): void
    {
        $this->setName('unlink');
        $this->setDescription('Remove local path override and restore previous constraints');
        $this->addArgument('package', InputArgument::REQUIRED, 'Package name');
        $this->addOption('no-update', null, InputOption::VALUE_NONE, 'Do not run composer update');
        $this->addOption('remove', null, InputOption::VALUE_NONE, 'For bootstrap packages: remove dependency');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $io = new SymfonyStyleOutput($style);

        return $this->tasks()->unlink(
            $this->stringArgument($input, 'package'),
            (bool) $input->getOption('no-update'),
            (bool) $input->getOption('remove'),
            $io
        );
    }
}
