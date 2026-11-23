<?php

namespace App\Modules\Core\Middleware;

use App\Modules\Core\Support\Modules\ModuleRegistry;
use App\Modules\Core\Support\Modules\ModuleScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleActive
{
    /**
     * Abort with 403 if the specified module is not active for the current request scope.
     *
     * Usage: Route::middleware('module.active:example')
     * Fail-closed: unregistered identifiers, unbound scope, and disabled modules all return 403.
     */
    public function handle(Request $request, Closure $next, string $identifierValue): Response
    {
        // The registry, not the app's identifier enum, decides which names exist,
        // so Core never depends on the modules an application happens to ship.
        $definition = app(ModuleRegistry::class)->all()->get($identifierValue);

        if ($definition === null || ! app()->has(ModuleScope::class)) {
            abort(403, 'Module not active.');
        }

        $scope = app(ModuleScope::class);

        if (! $scope->isActive($definition->identifier)) {
            abort(403, 'Module not active for this context.');
        }

        return $next($request);
    }
}
