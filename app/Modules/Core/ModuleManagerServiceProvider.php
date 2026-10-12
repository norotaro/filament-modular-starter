<?php

namespace App\Modules\Core;

use Illuminate\Support\ServiceProvider;

class ModuleManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Core first — it's foundational
        $this->app->register(CoreServiceProvider::class);

        // Auto-discover other module service providers
        foreach (glob(app_path('Modules/*/*ServiceProvider.php')) as $providerFile) {
            $moduleDir = dirname($providerFile);
            $module = basename($moduleDir);

            // Skip Core — already registered above
            if ($module === 'Core') {
                continue;
            }

            $fileName = basename($providerFile, '.php');
            $providerClass = "App\\Modules\\{$module}\\{$fileName}";

            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }

        // Auto-discover partner service providers
        foreach (glob(app_path('Partners/*/*ServiceProvider.php')) as $providerFile) {
            $partnerDir = dirname($providerFile);
            $partner = basename($partnerDir);
            $fileName = basename($providerFile, '.php');
            $providerClass = "App\\Partners\\{$partner}\\{$fileName}";

            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }
}
