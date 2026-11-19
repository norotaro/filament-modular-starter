<?php

use App\Modules\Core\Enums\ModuleIdentifier;
use App\Modules\Core\Support\Modules\ModuleDefinition;
use App\Modules\Core\Support\Modules\ModuleRegistry;
use App\Modules\Core\Support\Modules\ModuleScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('has the Example module registered in the module registry', function () {
    expect(app(ModuleRegistry::class)->has(ModuleIdentifier::Example))->toBeTrue();
});

it('has the disabled-by-default Sample module registered too', function () {
    expect(app(ModuleRegistry::class)->has(ModuleIdentifier::Sample))->toBeTrue();
});

it('locks the registry after boot so late define calls throw', function () {
    expect(fn () => app(ModuleRegistry::class)->define(new ModuleDefinition(
        identifier: ModuleIdentifier::Sample,
        applicabilityCheck: fn (?object $context): bool => false,
    )))->toThrow(RuntimeException::class);
});

it('activates Example and deactivates Sample from config defaults', function () {
    $scope = app(ModuleScope::class);

    expect($scope->isActive(ModuleIdentifier::Example))->toBeTrue()
        ->and($scope->isActive(ModuleIdentifier::Sample))->toBeFalse();
});
