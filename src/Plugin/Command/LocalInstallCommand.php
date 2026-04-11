<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Plugin\Command;

use HalfShellStudios\ComposerLink\Config\ComposerLinkConfigLoader;
use HalfShellStudios\ComposerLink\Services\ComposerProcessRunner;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Runs `composer install` with COMPOSER pointing at composer.local.json (and thus composer.local.lock).
 */
final class LocalInstallCommand extends AbstractComposerLinkCommand
{
    protected function configure(): void
    {
        $this->setName('local-install');
        $this->setDescription('Run composer install using composer.local.json / composer.local.lock');
        $this->addOption('no-dev', null, InputOption::VALUE_NONE, 'Skip installing require-dev packages');
        $this->addOption('no-progress', null, InputOption::VALUE_NONE, 'Disable the progress bar');
        $this->addOption('prefer-dist', null, InputOption::VALUE_NONE, 'Prefer dist installs');
        $this->addOption('prefer-source', null, InputOption::VALUE_NONE, 'Prefer source installs');
        $this->addOption('ignore-platform-reqs', null, InputOption::VALUE_NONE, 'Ignore platform requirements');
        $this->addOption('no-scripts', null, InputOption::VALUE_NONE, 'Skip running scripts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $root = $this->resolveProjectRoot();
        $config = ComposerLinkConfigLoader::loadForProject($root);
        $localComposerJson = $config['local_composer_json'];
        $composerBinary = $config['composer_binary'];
        $withAll = $config['update_with_all_dependencies'];
        if (! is_string($localComposerJson) || ! is_string($composerBinary) || ! is_bool($withAll)) {
            throw new RuntimeException('Invalid composer-link configuration.');
        }

        $local = $root . DIRECTORY_SEPARATOR . $localComposerJson;

        if (! is_file($local)) {
            $style->error(sprintf(
                'Missing [%s]. Run `composer local-bootstrap` first.',
                basename($local)
            ));

            return 1;
        }

        $extra = $this->installExtraFlags($input);

        $runner = new ComposerProcessRunner(
            $root,
            $composerBinary,
            $withAll,
            $local,
        );

        try {
            $runner->install($extra);
        } catch (RuntimeException $e) {
            $style->error($e->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * @return list<string>
     */
    private function installExtraFlags(InputInterface $input): array
    {
        $map = [
            'no-dev' => '--no-dev',
            'no-progress' => '--no-progress',
            'prefer-dist' => '--prefer-dist',
            'prefer-source' => '--prefer-source',
            'ignore-platform-reqs' => '--ignore-platform-reqs',
            'no-scripts' => '--no-scripts',
        ];
        $extra = [];
        foreach ($map as $option => $flag) {
            if ($input->getOption($option)) {
                $extra[] = $flag;
            }
        }

        return $extra;
    }
}
