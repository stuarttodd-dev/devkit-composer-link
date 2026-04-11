<?php

declare(strict_types=1);

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use HalfShellStudios\ComposerLink\Plugin\ComposerCommandProvider;
use HalfShellStudios\ComposerLink\Plugin\ComposerLinkPlugin;

test('plugin exposes command provider capability', function (): void {
    $plugin = new ComposerLinkPlugin();
    expect($plugin->getCapabilities())->toBe([
        CommandProviderCapability::class => ComposerCommandProvider::class,
    ]);
});

test('command provider registers all plugin commands', function (): void {
    $provider = new ComposerCommandProvider();
    $commands = $provider->getCommands();
    expect($commands)->toHaveCount(10);
    $names = array_map(static fn ($c) => $c->getName(), $commands);
    expect($names)->toContain('link')
        ->and($names)->toContain('add')
        ->and($names)->toContain('unlink')
        ->and($names)->toContain('promote')
        ->and($names)->toContain('linked')
        ->and($names)->toContain('refresh')
        ->and($names)->toContain('link-doctor')
        ->and($names)->toContain('local-bootstrap')
        ->and($names)->toContain('local-install')
        ->and($names)->toContain('link-help');
});
