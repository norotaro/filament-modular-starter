<?php

namespace App\Modules\Core\Filament;

use App\Modules\Users\Filament\Pages\EditProfile;
use Filament\Panel;
use Filament\PanelProvider as FilamentPanelProvider;
use Filament\Support\Colors\Color;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;

class PanelProvider extends FilamentPanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->databaseNotifications()
            ->profile(EditProfile::class, isSimple: false)
            ->plugins([
                EnvironmentIndicatorPlugin::make()
                    ->color(fn () => match (app()->environment()) {
                        'production' => null,
                        'staging' => Color::Orange,
                        default => Color::Blue,
                    }),
            ])
            ->font('Poppins');
    }
}
