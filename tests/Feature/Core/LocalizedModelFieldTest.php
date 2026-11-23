<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\LocalizedItem;

uses(LazilyRefreshDatabase::class);

/*
 * The fixture model wires both localized helpers onto one column: the cast for
 * whole-map reads and writes, the concern for per-locale display.
 */

beforeEach(function () {
    Schema::create('localized_items', function (Blueprint $table) {
        $table->id();
        $table->json('tagline')->nullable();
        $table->timestamps();
    });
});

it('persists a localized column and reads it back per locale', function () {
    config(['app.fallback_locale' => 'en']);

    $item = LocalizedItem::query()->create(['tagline' => ['es' => 'Hola', 'en' => 'Hello']]);
    $fresh = LocalizedItem::query()->findOrFail($item->id);

    expect($fresh->tagline)->toBe(['es' => 'Hola', 'en' => 'Hello'])
        ->and($fresh->getTranslation('tagline', 'es'))->toBe('Hola')
        ->and($fresh->getTranslation('tagline', 'fr'))->toBe('Hello');
});

it('saves a translation set through the concern', function () {
    $item = LocalizedItem::query()->create();

    $item->setTranslation('tagline', 'es', 'Hola')->save();

    expect(LocalizedItem::query()->findOrFail($item->id)->tagline)->toBe(['es' => 'Hola']);
});

it('reads an item with no tagline as no translations', function () {
    $item = LocalizedItem::query()->create();

    expect(LocalizedItem::query()->findOrFail($item->id)->tagline)->toBe([])
        ->and($item->getTranslation('tagline', 'es'))->toBe('');
});
