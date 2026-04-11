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

final class AddPackageCommand extends AbstractComposerLinkCommand
{
    protected function configure(): void
    {
        $this->setName('add');
        $this->setDescription('Add a package from a local path before it exists on Packagist (bootstrap)');
        $this->addArgument('package', InputArgument::REQUIRED, 'Package name');
        $this->addArgument('path', InputArgument::REQUIRED, 'Local path to the package');
        $this->addOption('constraint', 'c', InputOption::VALUE_REQUIRED, 'Version constraint (default @dev)');
        $this->addOption('no-dev', null, InputOption::VALUE_NONE, 'Add to require instead of require-dev');
        $this->addOption('no-update', null, InputOption::VALUE_NONE, 'Do not run composer update');
        $this->addOption('no-symlink', null, InputOption::VALUE_NONE, 'Disable symlink');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $io = new SymfonyStyleOutput($style);
        $root = $this->resolveProjectRoot();
        $path = PathHelper::qualifyPackagePath($this->stringArgument($input, 'path'), $root);
        $constraint = $input->getOption('constraint');

        return $this->tasks()->add(
            $this->stringArgument($input, 'package'),
            $path,
            is_string($constraint) && $constraint !== '' ? $constraint : null,
            (bool) $input->getOption('no-dev'),
            (bool) $input->getOption('no-update'),
            (bool) $input->getOption('no-symlink'),
            $io
        );
    }
}
