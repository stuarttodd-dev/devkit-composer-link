<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Plugin\Command;

use HalfShellStudios\ComposerLink\Output\SymfonyStyleOutput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class PromotePackageCommand extends AbstractComposerLinkCommand
{
    #[\Override]
    protected function configure(): void
    {
        $this->setName('promote');
        $this->setDescription('Switch from local path to a published version constraint');
        $this->addArgument('package', InputArgument::REQUIRED, 'Package name');
        $this->addArgument('constraint', InputArgument::REQUIRED, 'Published constraint, e.g. ^1.0');
        $this->addOption('no-update', null, InputOption::VALUE_NONE, 'Do not run composer update');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $io = new SymfonyStyleOutput($style);

        return $this->tasks()->promote(
            $this->stringArgument($input, 'package'),
            $this->stringArgument($input, 'constraint'),
            (bool) $input->getOption('no-update'),
            $io
        );
    }
}
