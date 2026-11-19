<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

/**
 * The one display rule for a localized JSON field (`['es' => '…', 'en' => '…']`).
 * Callers build the locale chain — the request or recipient locale is theirs to
 * know — and this decides which stored value is shown. A whitespace-only
 * value is no text: showing it would render a blank where another locale has
 * content.
 */
final class LocalizedText
{
    /**
     * The first chain locale holding text, else the first text in stored order,
     * else ''. The stored order keeps the any-locale pick deterministic per row.
     *
     * @param  array<array-key, mixed>|null  $translations
     * @param  array<int, string>  $chain
     */
    public static function resolve(?array $translations, array $chain): string
    {
        $translations ??= [];

        foreach ($chain as $locale) {
            $value = $translations[$locale] ?? null;

            if (self::isText($value)) {
                return $value;
            }
        }

        foreach ($translations as $value) {
            if (self::isText($value)) {
                return $value;
            }
        }

        return '';
    }

    /**
     * @phpstan-assert-if-true string $value
     */
    private static function isText(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }
}
