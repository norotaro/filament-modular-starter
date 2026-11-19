<?php

namespace App\Modules\Core\Middleware;

use App\Modules\Core\Enums\ModuleIdentifier;
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
     * Fail-closed: invalid identifiers, unbound scope, and disabled modules all return 403.
     */
    public function handle(Request $request, Closure $next, string $identifierValue): Response
    {
        $moduleIdentifier = ModuleIdentifier::tryFrom($identifierValue);

        if ($moduleIdentifier === null || ! app()->has(ModuleScope::class)) {
            abort(403, 'Module not active.');
        }

        $scope = app(ModuleScope::class);

        if (! $scope->isActive($moduleIdentifier)) {
            abort(403, 'Module not active for this context.');
        }

        return $next($request);
    }
}
