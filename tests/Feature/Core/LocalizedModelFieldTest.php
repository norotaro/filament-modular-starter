<?php

declare(strict_types=1);

use App\Modules\Example\Models\ExampleItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
 * The reference model wires both localized helpers onto one column: the cast for
 * whole-map reads and writes, the concern for per-locale display.
 */

it('persists a localized column and reads it back per locale', function () {
    config(['app.fallback_locale' => 'en']);

    $item = ExampleItem::factory()->create(['tagline' => ['es' => 'Hola', 'en' => 'Hello']]);
    $fresh = ExampleItem::query()->findOrFail($item->id);

    expect($fresh->tagline)->toBe(['es' => 'Hola', 'en' => 'Hello'])
        ->and($fresh->getTranslation('tagline', 'es'))->toBe('Hola')
        ->and($fresh->getTranslation('tagline', 'fr'))->toBe('Hello');
});

it('saves a translation set through the concern', function () {
    $item = ExampleItem::factory()->create();

    $item->setTranslation('tagline', 'es', 'Hola')->save();

    expect(ExampleItem::query()->findOrFail($item->id)->tagline)->toBe(['es' => 'Hola']);
});

it('reads an item with no tagline as no translations', function () {
    $item = ExampleItem::factory()->create();

    expect(ExampleItem::query()->findOrFail($item->id)->tagline)->toBe([])
        ->and($item->getTranslation('tagline', 'es'))->toBe('');
});
