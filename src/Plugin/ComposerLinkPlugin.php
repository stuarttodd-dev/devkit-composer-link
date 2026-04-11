<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

/**
 * Plugin lifecycle methods keep Composer's required signature; bodies are intentionally empty.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
final class ComposerLinkPlugin implements PluginInterface, Capable
{
    #[\Override]
    public function activate(Composer $composer, IOInterface $io): void
    {
    }

    #[\Override]
    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    #[\Override]
    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    #[\Override]
    public function getCapabilities(): array
    {
        return [
            CommandProviderCapability::class => ComposerCommandProvider::class,
        ];
    }
}
