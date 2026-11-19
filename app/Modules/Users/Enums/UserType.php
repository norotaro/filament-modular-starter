<?php

namespace App\Modules\Users\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum UserType: string implements HasColor, HasDescription, HasLabel
{
    case Admin = 'admin';
    case Participant = 'participant';
    case Reviewer = 'reviewer';
    case SuperAdmin = 'super_admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => __('Administrator'),
            self::Participant => __('Participant'),
            self::Reviewer => __('Reviewer'),
            self::SuperAdmin => __('Super Administrator'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'warning',
            self::Participant => 'success',
            self::Reviewer => 'info',
            self::SuperAdmin => 'danger',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Admin => __('Panel Administrator'),
            self::Participant => __('Self-registered Participant'),
            self::Reviewer => __('Assigned Reviewer'),
            self::SuperAdmin => __('System Admin with superpowers'),
        };
    }
}
