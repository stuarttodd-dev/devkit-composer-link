<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Plugin\Command;

use HalfShellStudios\ComposerLink\Output\SymfonyStyleOutput;
use HalfShellStudios\ComposerLink\Support\PathHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class LinkPackageCommand extends AbstractComposerLinkCommand
{
    #[\Override]
    protected function configure(): void
    {
        $this->setName('link');
        $this->setDescription('Link an existing Composer dependency to a local path');
        $this->addArgument('package', InputArgument::REQUIRED, 'Package name, e.g. vendor/package');
        $this->addArgument('path', InputArgument::REQUIRED, 'Local path to the package directory');
        $this->addOption('constraint', 'c', InputOption::VALUE_REQUIRED, 'Override version constraint');
        $this->addOption('no-update', null, InputOption::VALUE_NONE, 'Do not run composer update');
        $this->addOption('no-symlink', null, InputOption::VALUE_NONE, 'Disable symlink for the path repository');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $io = new SymfonyStyleOutput($style);
        $root = $this->resolveProjectRoot();
        $path = PathHelper::qualifyPackagePath($this->stringArgument($input, 'path'), $root);
        $constraint = $input->getOption('constraint');

        return $this->tasks()->link(
            $this->stringArgument($input, 'package'),
            $path,
            is_string($constraint) && $constraint !== '' ? $constraint : null,
            (bool) $input->getOption('no-update'),
            (bool) $input->getOption('no-symlink'),
            $io
        );
    }
}
