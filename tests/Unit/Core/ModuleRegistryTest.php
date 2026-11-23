<?php

use App\Modules\Core\Support\Modules\ModuleDefinition;
use App\Modules\Core\Support\Modules\ModuleRegistry;
use Tests\Fixtures\TestModule;

describe('ModuleRegistry', function () {
    test('defines an identifier exactly once and returns itself for chaining', function () {
        $registry = new ModuleRegistry;

        $returned = $registry->define(new ModuleDefinition(
            identifier: TestModule::Alpha,
            applicabilityCheck: fn (?object $context): bool => true,
        ));

        expect($returned)->toBe($registry)
            ->and($registry->has(TestModule::Alpha))->toBeTrue();
    });

    test('throws on a duplicate identifier', function () {
        $registry = new ModuleRegistry;
        $definition = new ModuleDefinition(
            identifier: TestModule::Alpha,
            applicabilityCheck: fn (?object $context): bool => true,
        );

        $registry->define($definition);

        expect(fn () => $registry->define($definition))
            ->toThrow(RuntimeException::class, "Module 'alpha' already registered.");
    });

    test('throws when defining into a locked registry', function () {
        $registry = new ModuleRegistry;
        $registry->lock();

        expect(fn () => $registry->define(new ModuleDefinition(
            identifier: TestModule::Alpha,
            applicabilityCheck: fn (?object $context): bool => true,
        )))->toThrow(RuntimeException::class, 'ModuleRegistry is locked after boot.');
    });

    test('the lock check runs before the duplicate check', function () {
        $registry = new ModuleRegistry;
        $registry->define(new ModuleDefinition(
            identifier: TestModule::Alpha,
            applicabilityCheck: fn (?object $context): bool => true,
        ));
        $registry->lock();

        expect(fn () => $registry->define(new ModuleDefinition(
            identifier: TestModule::Alpha,
            applicabilityCheck: fn (?object $context): bool => true,
        )))->toThrow(RuntimeException::class, 'ModuleRegistry is locked after boot.');
    });

    test('lock is irreversible by design', function () {
        $registry = new ModuleRegistry;
        $registry->lock();

        expect(method_exists($registry, 'unlock'))->toBeFalse();
    });

    test('get returns null for an unknown identifier', function () {
        $registry = new ModuleRegistry;

        expect($registry->get(TestModule::Beta))->toBeNull()
            ->and($registry->has(TestModule::Beta))->toBeFalse();
    });

    test('all exposes the definitions keyed by identifier value', function () {
        $registry = new ModuleRegistry;
        $registry->define(new ModuleDefinition(
            identifier: TestModule::Alpha,
            applicabilityCheck: fn (?object $context): bool => true,
        ));

        expect($registry->all())->toHaveKey('alpha');
    });
});
