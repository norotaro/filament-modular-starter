<?php

use App\Modules\Core\Support\Modules\ModuleDefinition;
use App\Modules\Core\Support\Modules\ModuleRegistry;
use App\Modules\Core\Support\Modules\ModuleScope;
use Tests\Fixtures\TestModule;
use Tests\TestCase;

uses(TestCase::class);

describe('ModuleScope', function () {
    test('evaluates the applicability closure against the given context', function () {
        $registry = new ModuleRegistry;
        $registry->define(new ModuleDefinition(
            identifier: TestModule::Alpha,
            applicabilityCheck: fn (?object $context): bool => $context !== null,
        ));

        $scope = ModuleScope::forContext($registry, new stdClass);

        expect($scope->isActive(TestModule::Alpha))->toBeTrue();
    });

    test('returns false for unknown identifier', function () {
        $registry = new ModuleRegistry;
        $scope = ModuleScope::forContext($registry, new stdClass);

        expect($scope->isActive(TestModule::Alpha))->toBeFalse();
    });

    test('always returns true under bypass', function () {
        $registry = new ModuleRegistry;
        $scope = ModuleScope::withBypass($registry);

        expect($scope->isActive(TestModule::Alpha))->toBeTrue()
            ->and($scope->isActive(TestModule::Beta))->toBeTrue();
    });

    test('a null context with no bypass delegates entirely to the closure', function () {
        $registry = new ModuleRegistry;
        $registry->define(new ModuleDefinition(
            identifier: TestModule::Alpha,
            applicabilityCheck: fn (?object $context): bool => $context === null,
        ));

        $scope = ModuleScope::forContext($registry, null);

        expect($scope->isActive(TestModule::Alpha))->toBeTrue();
    });

    test('caches isActive results per scope', function () {
        $callCount = 0;
        $registry = new ModuleRegistry;
        $registry->define(new ModuleDefinition(
            identifier: TestModule::Alpha,
            applicabilityCheck: function (?object $context) use (&$callCount): bool {
                $callCount++;

                return true;
            },
        ));

        $scope = ModuleScope::forContext($registry, new stdClass);
        $scope->isActive(TestModule::Alpha);
        $scope->isActive(TestModule::Alpha);
        $scope->isActive(TestModule::Alpha);

        expect($callCount)->toBe(1);
    });

    test('returns panel pages contributed by active modules only', function () {
        $registry = new ModuleRegistry;
        $registry->define(new ModuleDefinition(
            identifier: TestModule::Alpha,
            applicabilityCheck: fn (?object $context): bool => true,
            panelPages: ['alpha' => 'AlphaPage'],
        ));
        $registry->define(new ModuleDefinition(
            identifier: TestModule::Beta,
            applicabilityCheck: fn (?object $context): bool => false,
            panelPages: ['beta' => 'BetaPage'],
        ));

        $scope = ModuleScope::forContext($registry, new stdClass);
        $pages = $scope->activePanelPages();

        expect($pages)->toHaveKey('alpha')
            ->and($pages)->not->toHaveKey('beta');
    });

    test('returns sub-navigation contributed by active modules only', function () {
        $registry = new ModuleRegistry;
        $registry->define(new ModuleDefinition(
            identifier: TestModule::Alpha,
            applicabilityCheck: fn (?object $context): bool => true,
            panelSubNavigation: ['AlphaSubNav'],
        ));
        $registry->define(new ModuleDefinition(
            identifier: TestModule::Beta,
            applicabilityCheck: fn (?object $context): bool => false,
            panelSubNavigation: ['BetaSubNav'],
        ));

        $scope = ModuleScope::forContext($registry, new stdClass);

        expect($scope->activePanelSubNavigation())
            ->toContain('AlphaSubNav')
            ->not->toContain('BetaSubNav');
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
    beforeEach(function () {
        $registry = new ModuleRegistry;
        $registry->define(new ModuleDefinition(
            identifier: TestModule::Alpha,
            applicabilityCheck: fn (?object $context): bool => false,
        ));
        app()->instance(ModuleRegistry::class, $registry);
    });

    test('returns null when module is not registered in registry', function () {
        app()->instance(ModuleRegistry::class, new ModuleRegistry);

        expect(ModuleScope::resolve(TestModule::Alpha))->toBeNull();
    });

    test('returns null when scope auto-resolves with no context', function () {
        // The container's default scoped binding: context null, bypass false.
        expect(ModuleScope::resolve(TestModule::Alpha))->toBeNull();
    });

    test('returns true under bypass', function () {
        $scope = ModuleScope::withBypass(app(ModuleRegistry::class));
        app()->scoped(ModuleScope::class, fn () => $scope);

        expect(ModuleScope::resolve(TestModule::Alpha))->toBeTrue();
    });

    test('returns the closure result when a context is bound', function () {
        $scope = ModuleScope::forContext(app(ModuleRegistry::class), new stdClass);
        app()->scoped(ModuleScope::class, fn () => $scope);

        expect(ModuleScope::resolve(TestModule::Alpha))->toBeFalse();
    });
});
