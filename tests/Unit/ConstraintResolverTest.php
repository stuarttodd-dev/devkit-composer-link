<?php

declare(strict_types=1);

use HalfShellStudios\ComposerLink\Dto\InspectedPackage;
use HalfShellStudios\ComposerLink\Services\ConstraintResolver;

test('resolveForLink keeps constraint when local version satisfies caret range', function (): void {
    $r = new ConstraintResolver();
    $pkg = new InspectedPackage('v/p', '1.6.0', '/tmp');

    expect($r->resolveForLink('^1.5', $pkg, null))->toBe('^1.5');
});

test('resolveForLink uses dev when local version is unstable', function (): void {
    $r = new ConstraintResolver();
    $pkg = new InspectedPackage('v/p', 'dev-main', '/tmp');

    expect($r->resolveForLink('^1.5', $pkg, null))->toBe('@dev');
});

test('resolveForLink respects user constraint', function (): void {
    $r = new ConstraintResolver();
    $pkg = new InspectedPackage('v/p', '0.1.0', '/tmp');

    expect($r->resolveForLink('^2.0', $pkg, 'dev-main as 2.0.0'))->toBe('dev-main as 2.0.0');
});

test('resolveForBootstrap defaults to dev', function (): void {
    $r = new ConstraintResolver();

    expect($r->resolveForBootstrap(null))->toBe('@dev');
});

test('resolveForBootstrap uses explicit constraint', function (): void {
    $r = new ConstraintResolver();

    expect($r->resolveForBootstrap('^1.2'))->toBe('^1.2');
});

test('resolveForLink uses at-dev when local version empty', function (): void {
    $r = new ConstraintResolver();
    $pkg = new InspectedPackage('v/p', null, '/tmp');

    expect($r->resolveForLink('^1.0', $pkg, null))->toBe('@dev');
});

test('resolveForLink uses at-dev when semver check throws', function (): void {
    $r = new ConstraintResolver();
    $pkg = new InspectedPackage('v/p', 'not-a-semver-%%%', '/tmp');

    expect($r->resolveForLink('^1.0', $pkg, null))->toBe('@dev');
});
