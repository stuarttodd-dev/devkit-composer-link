<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Plugin\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Overview help for Composer Link — does not replace `composer help <command>`.
 */
final class LinkHelpCommand extends AbstractComposerLinkCommand
{
    protected function configure(): void
    {
        $this->setName('link-help');
        $this->setDescription('Composer Link overview: local manifests, commands, args, flags, and full help pointers');
        $this->setHelp(
            <<<'HELP'
This plugin keeps link state in packages-local.json and merges path repositories into composer.local.json
so committed composer.json / composer.lock stay the team baseline.

This command prints a summary table with arguments and options. For full descriptions and defaults, run:

  composer help link
  composer help add
  composer help unlink
  composer help promote
  composer help linked
  composer help refresh
  composer help link-doctor
  composer help local-bootstrap
  composer help local-install
  composer help link-help

Documentation: see the package README (Packagist / repository).
HELP
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $style->title('Composer Link');
        $style->writeln('Develop against local package checkouts without editing committed composer.json.');
        $style->writeln(
            'State: <info>packages-local.json</info> (gitignored). '
            . 'Working manifest: <info>composer.local.json</info> + lock (gitignored).'
        );
        $style->newLine();

        $style->section('Commands (arguments & options)');
        $style->table(
            ['Command', 'Summary', 'Arguments', 'Options'],
            [
                [
                    'composer link',
                    'Point an existing dependency at a local path',
                    'package, path',
                    '-c, --constraint=, --no-update, --no-symlink',
                ],
                [
                    'composer add',
                    'Bootstrap a package from disk before it is on Packagist',
                    'package, path',
                    '-c, --constraint=, --no-dev, --no-update, --no-symlink',
                ],
                [
                    'composer unlink',
                    'Drop the override or remove a bootstrapped package',
                    'package',
                    '--no-update, --remove',
                ],
                [
                    'composer promote',
                    'Use a published version constraint instead of a path',
                    'package, constraint',
                    '--no-update',
                ],
                [
                    'composer linked',
                    'List packages managed by this plugin',
                    '—',
                    '—',
                ],
                [
                    'composer refresh',
                    'Rebuild composer.local.json repos from packages-local.json',
                    '—',
                    '--no-update',
                ],
                [
                    'composer link-doctor',
                    'Check .gitignore, paths, and path-repo markers',
                    '—',
                    '—',
                ],
                [
                    'composer local-bootstrap',
                    'Copy composer.json (+ lock) → composer.local.*',
                    '—',
                    '-f, --force',
                ],
                [
                    'composer local-install',
                    'composer install using composer.local.json',
                    '—',
                    '--no-dev, --no-progress, --prefer-dist, --prefer-source, --ignore-platform-reqs, --no-scripts',
                ],
                [
                    'composer link-help',
                    'This overview',
                    '—',
                    '—',
                ],
            ]
        );

        $style->note(
            'Use `composer help <command>` for descriptions of each flag (e.g. `composer help link`). '
            . 'Full guide: package README.'
        );

        return 0;
    }
}
