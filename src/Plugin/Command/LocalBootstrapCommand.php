<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Plugin\Command;

use HalfShellStudios\ComposerLink\Config\ComposerLinkConfigLoader;
use HalfShellStudios\ComposerLink\Services\ComposerJsonManager;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Copies composer.json (and composer.lock when present) to composer.local.json / composer.local.lock.
 * Composer resolves the lock file name from the JSON basename (no separate COMPOSER_LOCK variable).
 */
final class LocalBootstrapCommand extends AbstractComposerLinkCommand
{
    protected function configure(): void
    {
        $this->setName('local-bootstrap');
        $this->setDescription('Create gitignored composer.local.json (+ .lock) from the committed composer.json');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing composer.local.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $root = $this->resolveProjectRoot();
        $config = ComposerLinkConfigLoader::loadForProject($root);
        $composerJson = $config['composer_json'];
        $localComposerJson = $config['local_composer_json'];
        $marker = $config['repository_marker'];
        if (! is_string($composerJson) || ! is_string($localComposerJson) || ! is_string($marker)) {
            throw new RuntimeException('Invalid composer-link configuration.');
        }

        $canonical = $root . DIRECTORY_SEPARATOR . $composerJson;
        $working = $root . DIRECTORY_SEPARATOR . $localComposerJson;

        $manager = new ComposerJsonManager(
            $canonical,
            $working,
            $marker,
        );

        try {
            $manager->bootstrapWorkingManifest((bool) $input->getOption('force'));
        } catch (\InvalidArgumentException $e) {
            $style->error($e->getMessage());

            return 1;
        }

        $lock = ComposerJsonManager::lockPathForComposerJson($working);
        $style->success(sprintf(
            'Wrote [%s]%s.',
            basename($working),
            is_file($lock) ? ' and [' . basename($lock) . ']' : ''
        ));
        $style->note(
            'Add those files to .gitignore. Run `composer local-install` '
            . '(or `COMPOSER=' . basename($working) . ' composer install`).'
        );

        return 0;
    }
}
