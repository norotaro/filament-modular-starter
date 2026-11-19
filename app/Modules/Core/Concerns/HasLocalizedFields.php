<?php

namespace App\Modules\Core\Concerns;

use App\Modules\Core\Support\LocalizedText;

/**
 * Localized-field concern with a spatie-compatible API.
 *
 * Stores each localized attribute as a JSON column keyed by locale:
 * {"es": "...", "en": "..."}.
 *
 * Fallback chain: requested locale → config('app.fallback_locale');
 * which value wins is {@see LocalizedText::resolve()}.
 */
trait HasLocalizedFields
{
    /**
     * With $useFallback off this is the exact stored value, blanks included:
     * form editing reads what is there, never another locale's text.
     */
    public function getTranslation(string $field, ?string $locale = null, bool $useFallback = true): string
    {
        $locale = $locale ?? app()->getLocale();
        $translations = $this->getTranslations($field);

        if (! $useFallback) {
            return isset($translations[$locale]) ? $translations[$locale] : '';
        }

        return LocalizedText::resolve($translations, array_values(array_unique(array_filter([
            $locale,
            (string) config('app.fallback_locale'),
        ]))));
    }

    public function setTranslation(string $field, string $locale, string $value): static
    {
        $translations = $this->getTranslations($field);
        $translations[$locale] = $value;

        $this->attributes[$field] = json_encode($translations);

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getTranslations(string $field): array
    {
        $raw = $this->attributes[$field] ?? null;

        if ($raw === null) {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function hasTranslation(string $field, string $locale): bool
    {
        $translations = $this->getTranslations($field);

        return isset($translations[$locale]) && $translations[$locale] !== '';
    }

    public function forgetTranslation(string $field, string $locale): static
    {
        $translations = $this->getTranslations($field);
        unset($translations[$locale]);

        $this->attributes[$field] = json_encode($translations);

        return $this;
    }
}
