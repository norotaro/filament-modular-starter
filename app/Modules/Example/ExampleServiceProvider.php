<?php

namespace App\Modules\Example;

use App\Modules\Core\Enums\ModuleIdentifier;
use App\Modules\Core\Support\Modules\ModuleDefinition;
use App\Modules\Core\Support\Modules\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Reference module: the smallest provider that participates in the module system.
 *
 * Auto-discovery: ModuleManagerServiceProvider globs module providers one
 * directory under app/Modules, so this file is registered without touching
 * bootstrap/providers.php — provided the filename equals the class name and
 * the class sits at the module root namespace. Anything else is silently
 * skipped.
 */
class ExampleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Modules declare themselves in register(), never in boot():
        // CoreServiceProvider locks the ModuleRegistry in its own boot() and
        // Core boots first, so a define() from any later boot() throws.
        $registry = $this->app->make(ModuleRegistry::class);

        $registry->define(new ModuleDefinition(
            identifier: ModuleIdentifier::Example,
            applicabilityCheck: fn (?object $context): bool => (bool) config('modules.example', true),
        ));

        // A second, disabled-by-default module so the inactive path is
        // demonstrable (module.active 403, ModuleScope::resolve() false).
        $registry->define(new ModuleDefinition(
            identifier: ModuleIdentifier::Sample,
            applicabilityCheck: fn (?object $context): bool => (bool) config('modules.sample', false),
        ));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadJsonTranslationsFrom(__DIR__.'/lang');
    }
}
