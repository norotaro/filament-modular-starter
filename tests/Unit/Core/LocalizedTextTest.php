<?php

declare(strict_types=1);

use App\Modules\Core\Support\LocalizedText;

describe('LocalizedText::resolve', function () {
    it('returns the first chain locale that holds text, in chain order', function () {
        $translations = ['es' => 'Hola', 'en' => 'Hello'];

        expect(LocalizedText::resolve($translations, ['en', 'es']))->toBe('Hello')
            ->and(LocalizedText::resolve($translations, ['es', 'en']))->toBe('Hola');
    });

    it('skips a chain locale whose value is empty or whitespace only', function () {
        $translations = ['en' => " \t\n ", 'fr' => '', 'es' => 'Hola'];

        expect(LocalizedText::resolve($translations, ['en', 'fr', 'es']))->toBe('Hola');
    });

    it('falls back to any locale, in stored order, when the chain finds nothing', function () {
        $translations = ['es' => '   ', 'pt' => 'Olá', 'de' => 'Hallo'];

        expect(LocalizedText::resolve($translations, ['en', 'es']))->toBe('Olá');
    });

    it('treats a non-string value as missing', function () {
        $translations = ['en' => ['nested'], 'es' => 42, 'pt' => null, 'de' => 'Hallo'];

        expect(LocalizedText::resolve($translations, ['en', 'es', 'pt']))->toBe('Hallo');
    });

    it('returns an empty string when there is nothing to show', function (?array $translations) {
        expect(LocalizedText::resolve($translations, ['es', 'en']))->toBe('');
    })->with([
        'null' => [null],
        'empty array' => [[]],
        'only blanks' => [['es' => '', 'en' => '  ', 'pt' => null]],
    ]);

    it('returns the value exactly as stored, without trimming it', function () {
        expect(LocalizedText::resolve(['es' => '  Hola  '], ['es']))->toBe('  Hola  ')
            ->and(LocalizedText::resolve(['pt' => "\tOlá\n"], ['es']))->toBe("\tOlá\n");
    });
});
