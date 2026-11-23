<?php

use App\Modules\Core\Support\Modules\ModuleDefinition;
use App\Modules\Core\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Fixtures\TestModule;

uses(LazilyRefreshDatabase::class);

it('locks the registry after boot so late define calls throw', function () {
    expect(fn () => app(ModuleRegistry::class)->define(new ModuleDefinition(
        identifier: TestModule::Alpha,
        applicabilityCheck: fn (?object $context): bool => false,
    )))->toThrow(RuntimeException::class, 'ModuleRegistry is locked after boot.');
});
