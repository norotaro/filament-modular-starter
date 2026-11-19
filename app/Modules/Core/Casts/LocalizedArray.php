<?php

namespace App\Modules\Core\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent cast for JSON-per-locale columns.
 *
 * Stores/retrieves an associative array keyed by locale:
 * {"es": "...", "en": "..."}.
 *
 * Null columns are returned as empty arrays.
 */
class LocalizedArray implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  Model  $model
     * @param  array<string, mixed>  $attributes
     * @return array<string, string>
     */
    public function get($model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  Model  $model
     * @param  array<string, mixed>  $attributes
     */
    public function set($model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value);
    }
}
