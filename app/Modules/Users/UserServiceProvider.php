<?php

namespace App\Modules\Users;

use App\Modules\Users\Models\Identity;
use App\Modules\Users\Models\User;
use App\Modules\Users\Policies\UserPolicy;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind User model for Filament's Import feature
        $this->app->bind(Authenticatable::class, User::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadJsonTranslationsFrom(__DIR__.'/lang');

        // The per-user display timezone is registered once at application
        // boot, not per panel: one lazy resolver serves every panel
        // identically. This registration is load-bearing for all panels —
        // the shared Core Filament panel base depends on it.
        FilamentTimezone::set(function () {
            $user = Auth::user();

            if ($user !== null && $user->timezone !== null) {
                return $user->timezone;
            }

            return config('app.timezone');
        });

        Gate::policy(
            Identity::class,
            UserPolicy::class,
        );
    }
}
