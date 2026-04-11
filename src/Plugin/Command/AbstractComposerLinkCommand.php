<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Plugin\Command;

use Composer\Command\BaseCommand;
use HalfShellStudios\ComposerLink\ComposerLinkFactory;
use HalfShellStudios\ComposerLink\ComposerLinkTasks;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractComposerLinkCommand extends BaseCommand
{
    protected function tasks(): ComposerLinkTasks
    {
        return ComposerLinkFactory::createFromProjectRoot($this->resolveProjectRoot());
    }

    protected function resolveProjectRoot(): string
    {
        $config = $this->requireComposer()->getConfig();
        $vendorDir = $config->get('vendor-dir');
        if (! is_string($vendorDir) || $vendorDir === '') {
            throw new RuntimeException('Could not resolve vendor directory.');
        }

        $real = realpath($vendorDir);
        if ($real === false) {
            throw new RuntimeException('Could not resolve vendor directory.');
        }

        return dirname($real);
    }

    protected function stringArgument(InputInterface $input, string $name): string
    {
        $value = $input->getArgument($name);
        if (! is_string($value)) {
            throw new RuntimeException('Expected string argument: ' . $name);
        }

        return $value;
    }
}
