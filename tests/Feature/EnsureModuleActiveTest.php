<?php

use App\Modules\Core\Middleware\EnsureModuleActive;
use App\Modules\Core\Support\Modules\ModuleDefinition;
use App\Modules\Core\Support\Modules\ModuleRegistry;
use App\Modules\Core\Support\Modules\ModuleScope;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Fixtures\TestModule;

/*
 * The middleware is exercised directly (unit-style inside the Feature suite)
 * because the skeleton has no guarded routes to hit: the module.active alias
 * exists for the app you are about to build on top of this.
 */

beforeEach(function () {
    $registry = new ModuleRegistry;
    $registry->define(new ModuleDefinition(
        identifier: TestModule::Alpha,
        applicabilityCheck: fn (?object $context): bool => true,
    ));
    $registry->define(new ModuleDefinition(
        identifier: TestModule::Beta,
        applicabilityCheck: fn (?object $context): bool => false,
    ));
    app()->instance(ModuleRegistry::class, $registry);
});

function runModuleActive(string $identifier): Response
{
    $middleware = new EnsureModuleActive;

    return $middleware->handle(
        Request::create('/guarded', 'GET'),
        fn () => new Response('OK', 200),
        $identifier,
    );
}

it('allows the request when the module is active', function () {
    expect(runModuleActive('alpha')->getStatusCode())->toBe(200);
});

it('aborts 403 when the module resolves inactive', function () {
    runModuleActive('beta');
})->throws(HttpException::class, 'Module not active for this context.');

it('aborts 403 for an unknown module identifier', function () {
    runModuleActive('no-such-module');
})->throws(HttpException::class, 'Module not active.');

it('allows every registered module under a bypass scope', function () {
    app()->scoped(ModuleScope::class, fn () => ModuleScope::withBypass(app(ModuleRegistry::class)));

    expect(runModuleActive('beta')->getStatusCode())->toBe(200);
});

it('registers the module.active alias in CoreServiceProvider', function () {
    $middleware = app('router')->getMiddleware();

    expect($middleware)->toHaveKey('module.active')
        ->and($middleware['module.active'])->toBe(EnsureModuleActive::class);
});
