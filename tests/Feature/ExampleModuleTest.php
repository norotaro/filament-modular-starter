<?php

use App\Modules\Core\Enums\ModuleIdentifier;
use App\Modules\Core\Support\Modules\ModuleRegistry;
use App\Modules\Core\Support\Modules\ModuleScope;
use App\Modules\Example\Filament\Tables\Columns\ExampleNameColumn;
use App\Modules\Example\Models\ExampleItem;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

it('runs the module-local migration shipped by the Example provider', function () {
    expect(DB::connection()->getSchemaBuilder()->hasTable('example_items'))->toBeTrue();
});

it('resolves the module-local factory through HasModuleFactory', function () {
    $item = ExampleItem::factory()->create();

    $this->assertModelExists($item);
    expect($item->name)->not->toBeEmpty();
});

it('builds the Filament column atom outside any table context', function () {
    expect(ExampleNameColumn::make())->toBeInstanceOf(TextColumn::class);
});

it('registers Example and the disabled-by-default Sample in the module registry', function () {
    $registry = app(ModuleRegistry::class);

    expect($registry->has(ModuleIdentifier::Example))->toBeTrue()
        ->and($registry->has(ModuleIdentifier::Sample))->toBeTrue();
});

it('activates Example and deactivates Sample from config defaults', function () {
    $scope = app(ModuleScope::class);

    expect($scope->isActive(ModuleIdentifier::Example))->toBeTrue()
        ->and($scope->isActive(ModuleIdentifier::Sample))->toBeFalse();
});
