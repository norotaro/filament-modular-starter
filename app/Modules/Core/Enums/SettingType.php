<?php

namespace App\Modules\Core\Enums;

use Filament\Support\Contracts\HasLabel;

enum SettingType: string implements HasLabel
{
    case String = 'string';
    case Integer = 'integer';
    case Boolean = 'boolean';

    /**
     * Get the label for display purposes
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::String => __('Text'),
            self::Integer => __('Number (Integer)'),
            self::Boolean => __('Yes/No'),
        };
    }

    /**
     * Get validation rules for this type
     */
    public function validationRules(): array
    {
        return match ($this) {
            self::String => ['string'],
            self::Integer => ['integer'],
            self::Boolean => ['boolean'],
        };
    }

    /**
     * Convert a raw value to the appropriate PHP type
     */
    public function convertValue(?string $value): string|int|bool|null
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::String => $value,
            self::Integer => intval($value),
            self::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        };
    }

    /**
     * Format a value for storage
     */
    public function formatForStorage(mixed $value): string
    {
        return match ($this) {
            self::Boolean => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }
}
