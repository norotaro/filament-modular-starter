<?php

use App\Modules\Core\Enums\ModuleIdentifier;
use App\Modules\Core\Support\Modules\ModuleDefinition;
use App\Modules\Core\Support\Modules\ModuleRegistry;
use App\Modules\Core\Support\Modules\ModuleScope;
use Tests\TestCase;

uses(TestCase::class);

describe('ModuleScope', function () {
    test('evaluates the applicability closure against the given context', function () {
        $registry = new ModuleRegistry;
        $registry->define(new ModuleDefinition(
            identifier: ModuleIdentifier::Example,
            applicabilityCheck: fn (?object $context): bool => $context !== null,
        ));

        $scope = ModuleScope::forContext($registry, new stdClass);

        expect($scope->isActive(ModuleIdentifier::Example))->toBeTrue();
    });

    test('returns false for unknown identifier', function () {
        $registry = new ModuleRegistry;
        $scope = ModuleScope::forContext($registry, new stdClass);

        expect($scope->isActive(ModuleIdentifier::Example))->toBeFalse();
    });

    test('always returns true under bypass', function () {
        $registry = new ModuleRegistry;
        $scope = ModuleScope::withBypass($registry);

        expect($scope->isActive(ModuleIdentifier::Example))->toBeTrue()
            ->and($scope->isActive(ModuleIdentifier::Sample))->toBeTrue();
    });

    test('a null context with no bypass delegates entirely to the closure', function () {
        $registry = new ModuleRegistry;
        $registry->define(new ModuleDefinition(
            identifier: ModuleIdentifier::Example,
            applicabilityCheck: fn (?object $context): bool => $context === null,
        ));

        $scope = ModuleScope::forContext($registry, null);

        expect($scope->isActive(ModuleIdentifier::Example))->toBeTrue();
    });

    test('caches isActive results per scope', function () {
        $callCount = 0;
        $registry = new ModuleRegistry;
        $registry->define(new ModuleDefinition(
            identifier: ModuleIdentifier::Example,
            applicabilityCheck: function (?object $context) use (&$callCount): bool {
                $callCount++;

                return true;
            },
        ));

        $scope = ModuleScope::forContext($registry, new stdClass);
        $scope->isActive(ModuleIdentifier::Example);
        $scope->isActive(ModuleIdentifier::Example);
        $scope->isActive(ModuleIdentifier::Example);

        expect($callCount)->toBe(1);
    });

    test('returns panel pages contributed by active modules only', function () {
        $registry = new ModuleRegistry;
        $registry->define(new ModuleDefinition(
            identifier: ModuleIdentifier::Example,
            applicabilityCheck: fn (?object $context): bool => true,
            panelPages: ['example' => 'ExamplePage'],
        ));
        $registry->define(new ModuleDefinition(
            identifier: ModuleIdentifier::Sample,
            applicabilityCheck: fn (?object $context): bool => false,
            panelPages: ['sample' => 'SamplePage'],
        ));

        $scope = ModuleScope::forContext($registry, new stdClass);
        $pages = $scope->activePanelPages();

        expect($pages)->toHaveKey('example')
            ->and($pages)->not->toHaveKey('sample');
    });

    test('returns sub-navigation contributed by active modules only', function () {
        $registry = new ModuleRegistry;
        $registry->define(new ModuleDefinition(
            identifier: ModuleIdentifier::Example,
            applicabilityCheck: fn (?object $context): bool => true,
            panelSubNavigation: ['ExampleSubNav'],
        ));
        $registry->define(new ModuleDefinition(
            identifier: ModuleIdentifier::Sample,
            applicabilityCheck: fn (?object $context): bool => false,
            panelSubNavigation: ['SampleSubNav'],
        ));

        $scope = ModuleScope::forContext($registry, new stdClass);

        expect($scope->activePanelSubNavigation())
            ->toContain('ExampleSubNav')
            ->not->toContain('SampleSubNav');
    });

    test('is bound as scoped in the container', function () {
        $scope1 = app(ModuleScope::class);
        $scope2 = app(ModuleScope::class);

        expect($scope1)->toBe($scope2)
            ->and($scope1)->toBeInstanceOf(ModuleScope::class);
    });

    test('the container default carries no context and no bypass', function () {
        $scope = app(ModuleScope::class);

        expect($scope->context())->toBeNull()
            ->and($scope->isBypass())->toBeFalse();
    });
});

describe('ModuleScope::resolve()', function () {
    test('returns null when module is not registered in registry', function () {
        app()->instance(ModuleRegistry::class, new ModuleRegistry);

        expect(ModuleScope::resolve(ModuleIdentifier::Example))->toBeNull();
    });

    test('returns null when scope auto-resolves with no context', function () {
        // The app registry is locked with Example/Sample defined; the default
        // scoped binding auto-resolves context null + bypass false.
        expect(ModuleScope::resolve(ModuleIdentifier::Example))->toBeNull();
    });

    test('returns true under bypass', function () {
        $scope = ModuleScope::withBypass(app(ModuleRegistry::class));
        app()->scoped(ModuleScope::class, fn () => $scope);

        expect(ModuleScope::resolve(ModuleIdentifier::Example))->toBeTrue();
    });

    test('returns the closure result when a context is bound', function () {
        $scope = ModuleScope::forContext(app(ModuleRegistry::class), new stdClass);
        app()->scoped(ModuleScope::class, fn () => $scope);

        expect(ModuleScope::resolve(ModuleIdentifier::Sample))->toBeFalse();
    });
});
