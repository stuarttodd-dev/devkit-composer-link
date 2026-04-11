<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Plugin;

use Composer\Plugin\Capability\CommandProvider;
use HalfShellStudios\ComposerLink\Plugin\Command\AddPackageCommand;
use HalfShellStudios\ComposerLink\Plugin\Command\DoctorLinkCommand;
use HalfShellStudios\ComposerLink\Plugin\Command\LinkedPackagesCommand;
use HalfShellStudios\ComposerLink\Plugin\Command\LinkHelpCommand;
use HalfShellStudios\ComposerLink\Plugin\Command\LinkPackageCommand;
use HalfShellStudios\ComposerLink\Plugin\Command\PromotePackageCommand;
use HalfShellStudios\ComposerLink\Plugin\Command\LocalBootstrapCommand;
use HalfShellStudios\ComposerLink\Plugin\Command\LocalInstallCommand;
use HalfShellStudios\ComposerLink\Plugin\Command\RefreshLinkCommand;
use HalfShellStudios\ComposerLink\Plugin\Command\UnlinkPackageCommand;

final class ComposerCommandProvider implements CommandProvider
{
    #[\Override]
    public function getCommands(): array
    {
        return [
            new LinkPackageCommand(),
            new AddPackageCommand(),
            new UnlinkPackageCommand(),
            new PromotePackageCommand(),
            new LinkedPackagesCommand(),
            new RefreshLinkCommand(),
            new DoctorLinkCommand(),
            new LocalBootstrapCommand(),
            new LocalInstallCommand(),
            new LinkHelpCommand(),
        ];
    }
}
