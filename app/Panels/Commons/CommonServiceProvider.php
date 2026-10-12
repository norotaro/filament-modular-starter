<?php

namespace App\Panels\Commons;

use App\Modules\Users\Enums\UserType;
use App\Panels\Commons\Enums\PanelID;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class CommonServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__.'/lang');

        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        $this->registerAuthProviders();

        $this->registerFilamentColors();
    }

    /**
     * Restrict authentication per panel: each panel only lets its own role
     * sign in. The strict 1:1 rule lives in PanelID::isAccessibleBy(); this
     * provider is what makes the login attempt itself fail for the wrong
     * role, before any panel authorization runs.
     */
    private function registerAuthProviders(): void
    {
        Auth::provider('eloquent-panel-user', function ($app, array $config) {
            $provider = new EloquentUserProvider($app['hash'], $config['model']);

            $panel = Filament::getCurrentPanel();
            $panelId = PanelID::tryFrom($panel?->getId());

            $provider->withQuery(fn ($query) => match ($panelId) {
                PanelID::SuperAdmin => $query->where('type', UserType::SuperAdmin),
                PanelID::Admin => $query->where('type', UserType::Admin),
                default => $query,
            });

            return $provider;
        });
    }

    private function registerFilamentColors(): void
    {
        FilamentColor::register([
            'primary' => [ // Blue
                50 => 'oklch(0.95 0.05 260)',
                100 => 'oklch(0.90 0.07 260)',
                200 => 'oklch(0.82 0.10 260)',
                300 => 'oklch(0.74 0.12 260)',
                400 => 'oklch(0.66 0.14 260)',
                500 => 'oklch(0.58 0.15 260)',
                600 => 'oklch(0.50 0.15 260)',
                700 => 'oklch(0.42 0.13 260)',
                800 => 'oklch(0.34 0.11 260)',
                900 => 'oklch(0.26 0.09 260)',
                950 => 'oklch(0.18 0.07 260)',
            ],
            'accent' => [ // Orange
                50 => 'oklch(0.95 0.07 60)',
                100 => 'oklch(0.90 0.10 60)',
                200 => 'oklch(0.84 0.13 60)',
                300 => 'oklch(0.76 0.15 60)',
                400 => 'oklch(0.68 0.17 60)',
                500 => 'oklch(0.60 0.19 60)',
                600 => 'oklch(0.52 0.18 60)',
                700 => 'oklch(0.44 0.16 60)',
                800 => 'oklch(0.36 0.14 60)',
                900 => 'oklch(0.28 0.12 60)',
                950 => 'oklch(0.20 0.10 60)',
            ],
            'info' => [
                50 => 'oklch(0.95 0.05 230)',
                100 => 'oklch(0.90 0.07 230)',
                200 => 'oklch(0.82 0.10 230)',
                300 => 'oklch(0.74 0.12 230)',
                400 => 'oklch(0.66 0.14 230)',
                500 => 'oklch(0.58 0.16 230)',
                600 => 'oklch(0.50 0.15 230)',
                700 => 'oklch(0.42 0.13 230)',
                800 => 'oklch(0.34 0.11 230)',
                900 => 'oklch(0.26 0.09 230)',
                950 => 'oklch(0.18 0.07 230)',
            ],
            'warning' => [
                50 => 'oklch(0.96 0.07 85)',
                100 => 'oklch(0.92 0.09 85)',
                200 => 'oklch(0.86 0.11 85)',
                300 => 'oklch(0.78 0.13 85)',
                400 => 'oklch(0.70 0.15 85)',
                500 => 'oklch(0.62 0.17 85)',
                600 => 'oklch(0.54 0.16 85)',
                700 => 'oklch(0.46 0.14 85)',
                800 => 'oklch(0.38 0.12 85)',
                900 => 'oklch(0.30 0.10 85)',
                950 => 'oklch(0.22 0.08 85)',
            ],
            'success' => [
                50 => 'oklch(0.95 0.05 150)',
                100 => 'oklch(0.90 0.07 150)',
                200 => 'oklch(0.82 0.10 150)',
                300 => 'oklch(0.74 0.12 150)',
                400 => 'oklch(0.66 0.14 150)',
                500 => 'oklch(0.58 0.16 150)',
                600 => 'oklch(0.50 0.15 150)',
                700 => 'oklch(0.42 0.13 150)',
                800 => 'oklch(0.34 0.11 150)',
                900 => 'oklch(0.26 0.09 150)',
                950 => 'oklch(0.18 0.07 150)',
            ],
            'danger' => [ // Error
                50 => 'oklch(0.95 0.05 30)',
                100 => 'oklch(0.90 0.07 30)',
                200 => 'oklch(0.82 0.10 30)',
                300 => 'oklch(0.74 0.12 30)',
                400 => 'oklch(0.66 0.14 30)',
                500 => 'oklch(0.58 0.16 30)',
                600 => 'oklch(0.50 0.15 30)',
                700 => 'oklch(0.42 0.13 30)',
                800 => 'oklch(0.34 0.11 30)',
                900 => 'oklch(0.26 0.09 30)',
                950 => 'oklch(0.18 0.07 30)',
            ],
        ]);
    }
}
