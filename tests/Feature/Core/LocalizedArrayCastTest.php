<?php

declare(strict_types=1);

use App\Modules\Core\Casts\LocalizedArray;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

if (! function_exists('localizedArrayModel')) {
    /**
     * Anonymous, because a named class declared here is redeclared when Pest
     * loads this file a second time in the same process.
     */
    function localizedArrayModel(): Model
    {
        return new class extends Model
        {
            protected $table = 'localized_array_rows';

            protected $guarded = [];

            protected function casts(): array
            {
                return ['name' => LocalizedArray::class];
            }
        };
    }
}

beforeEach(function () {
    Schema::create('localized_array_rows', function (Blueprint $table) {
        $table->id();
        $table->json('name')->nullable();
        $table->timestamps();
    });
});

it('stores a locale map as JSON and reads it back as an array', function () {
    $row = localizedArrayModel()->newQuery()->create(['name' => ['es' => 'Hola', 'en' => 'Hello']]);

    expect($row->getRawOriginal('name'))->toBe('{"es":"Hola","en":"Hello"}')
        ->and(localizedArrayModel()->newQuery()->findOrFail($row->getKey())->getAttribute('name'))
        ->toBe(['es' => 'Hola', 'en' => 'Hello']);
});

it('stores null and reads a null column as an empty array', function () {
    $row = localizedArrayModel()->newQuery()->create(['name' => null]);

    expect($row->getRawOriginal('name'))->toBeNull()
        ->and(localizedArrayModel()->newQuery()->findOrFail($row->getKey())->getAttribute('name'))->toBe([]);
});

it('replaces the whole map on update, keeping the locales it is given', function () {
    $row = localizedArrayModel()->newQuery()->create(['name' => ['es' => 'Hola']]);

    $row->setAttribute('name', ['es' => 'Hola', 'en' => 'Hello'])->save();

    expect(localizedArrayModel()->newQuery()->findOrFail($row->getKey())->getAttribute('name'))
        ->toBe(['es' => 'Hola', 'en' => 'Hello']);
});

it('reads a column that does not hold a JSON object as an empty array', function () {
    $row = localizedArrayModel()->newQuery()->create(['name' => null]);
    localizedArrayModel()->newQuery()->whereKey($row->getKey())->toBase()->update(['name' => '"plain"']);

    expect(localizedArrayModel()->newQuery()->findOrFail($row->getKey())->getAttribute('name'))->toBe([]);
});
