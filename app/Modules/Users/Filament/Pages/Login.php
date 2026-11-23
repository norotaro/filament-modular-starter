<?php

namespace App\Modules\Users\Filament\Pages;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Login page for the panels. It renders no registration link because the
 * starter ships no self-registration.
 */
class Login extends BaseLogin
{
    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return __('filament-panels::auth/pages/login.multi_factor.subheading');
        }

        return null;
    }
}
