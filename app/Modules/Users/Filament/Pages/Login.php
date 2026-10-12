<?php

namespace App\Modules\Users\Filament\Pages;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Login page for Admin and Reviewers
 * In this login page we don't render the registration link because the registration is by invitation only
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
