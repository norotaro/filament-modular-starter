<?php

namespace App\Modules\Core;

use App\Modules\Core\Middleware\EnsureModuleActive;
use App\Modules\Core\Support\Modules\ModuleRegistry;
use App\Modules\Core\Support\Modules\ModuleScope;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class);
        $this->app->scoped(ModuleScope::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // After this point, no ServiceProvider may call `$registry->define(...)`.
        // Register modules in service provider `register()` methods, not `boot()`.
        app(ModuleRegistry::class)->lock();

        app('router')->aliasMiddleware('module.active', EnsureModuleActive::class);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/lang');
        $this->loadJsonTranslationsFrom(__DIR__.'/lang');
    }
}
