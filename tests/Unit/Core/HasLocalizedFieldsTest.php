<?php

declare(strict_types=1);

use App\Modules\Core\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

uses(TestCase::class);

if (! function_exists('localizedFieldsModel')) {
    /**
     * Anonymous, because a named class declared here is redeclared when Pest
     * loads this file a second time in the same process.
     */
    function localizedFieldsModel(): Model
    {
        return new class extends Model
        {
            use HasLocalizedFields;

            protected $guarded = [];
        };
    }
}

describe('HasLocalizedFields', function () {
    beforeEach(function () {
        config(['app.fallback_locale' => 'en']);
    });

    it('sets and gets a translation for a given locale', function () {
        $model = localizedFieldsModel()->setTranslation('name', 'es', 'Hola');

        expect($model->getTranslation('name', 'es'))->toBe('Hola');
    });

    it('stores several locales without overwriting each other', function () {
        $model = localizedFieldsModel()
            ->setTranslation('name', 'es', 'Hola')
            ->setTranslation('name', 'en', 'Hello');

        expect($model->getTranslations('name'))->toBe(['es' => 'Hola', 'en' => 'Hello'])
            ->and($model->getAttributes()['name'])->toBe('{"es":"Hola","en":"Hello"}');
    });

    it('reads the current app locale when no locale is given', function () {
        app()->setLocale('es');

        $model = localizedFieldsModel()
            ->setTranslation('name', 'es', 'Hola')
            ->setTranslation('name', 'en', 'Hello');

        expect($model->getTranslation('name'))->toBe('Hola');
    });

    it('falls back to the app fallback locale when the requested one is missing', function () {
        config(['app.fallback_locale' => 'es']);

        $model = localizedFieldsModel()
            ->setTranslation('name', 'pt', 'Olá')
            ->setTranslation('name', 'es', 'Hola');

        expect($model->getTranslation('name', 'fr'))->toBe('Hola');
    });

    it('skips a whitespace-only requested locale and falls through the chain', function () {
        $model = localizedFieldsModel()
            ->setTranslation('name', 'es', '   ')
            ->setTranslation('name', 'en', 'Hello');

        expect($model->getTranslation('name', 'es'))->toBe('Hello');
    });

    it('falls back to any stored locale when the chain holds no text', function () {
        $model = localizedFieldsModel()
            ->setTranslation('name', 'en', ' ')
            ->setTranslation('name', 'pt', 'Olá');

        expect($model->getTranslation('name', 'es'))->toBe('Olá');
    });

    it('reads the exact locale untouched when fallback is off, blanks included', function () {
        $model = localizedFieldsModel()
            ->setTranslation('name', 'es', '   ')
            ->setTranslation('name', 'en', 'Hello');

        expect($model->getTranslation('name', 'es', useFallback: false))->toBe('   ')
            ->and($model->getTranslation('name', 'fr', useFallback: false))->toBe('');
    });

    it('returns an empty string for a field with no stored value', function () {
        expect(localizedFieldsModel()->getTranslation('name', 'es'))->toBe('')
            ->and(localizedFieldsModel()->getTranslations('name'))->toBe([]);
    });

    it('treats an undecodable stored value as no translations', function () {
        $model = localizedFieldsModel()->setRawAttributes(['name' => 'not json']);

        expect($model->getTranslations('name'))->toBe([]);
    });

    it('reports whether a locale holds a non-empty translation', function () {
        $model = localizedFieldsModel()
            ->setTranslation('name', 'es', 'Hola')
            ->setTranslation('name', 'en', '');

        expect($model->hasTranslation('name', 'es'))->toBeTrue()
            ->and($model->hasTranslation('name', 'en'))->toBeFalse()
            ->and($model->hasTranslation('name', 'fr'))->toBeFalse();
    });

    it('forgets one locale and keeps the others', function () {
        $model = localizedFieldsModel()
            ->setTranslation('name', 'es', 'Hola')
            ->setTranslation('name', 'en', 'Hello')
            ->forgetTranslation('name', 'en');

        expect($model->getTranslations('name'))->toBe(['es' => 'Hola']);
    });
});
