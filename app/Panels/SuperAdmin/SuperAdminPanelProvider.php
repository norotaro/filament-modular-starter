<?php

namespace App\Panels\SuperAdmin;

use App\Modules\Core\Filament\PanelProvider;
use App\Modules\Users\Filament\Pages\Login;
use App\Panels\Commons\Enums\PanelID;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return parent::panel($panel)
            ->id(PanelID::SuperAdmin->value)
            ->path('super-admin')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Red,
            ])
            ->favicon(asset('favicon.ico'))
            ->viteTheme('resources/css/filament/super-admin/theme.css')
            ->discoverResources(in: app_path('Panels/SuperAdmin/Resources'), for: 'App\\Panels\\SuperAdmin\\Resources')
            ->discoverPages(in: app_path('Panels/SuperAdmin/Pages'), for: 'App\\Panels\\SuperAdmin\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Panels/SuperAdmin/Widgets'), for: 'App\\Panels\\SuperAdmin\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__.'/lang');
    }
}
