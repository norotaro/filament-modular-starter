<?php

use App\Modules\Core\Support\RedirectUrlSigner;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Guest redirect for signed links: when an unauthenticated user opens a
        // URL carrying a signed ?r= parameter, send them to the panel login
        // encoded in the token and remember the intended URL. Without a valid
        // signature, fall back to the app URL so nothing crashes when no
        // 'login' route exists.
        $middleware->redirectGuestsTo(function (Request $request): string {
            $signer = app(RedirectUrlSigner::class);
            $panelUrl = $signer->verify($request->query($signer->parameter()));

            if ($panelUrl !== null) {
                session()->put('url.intended', $request->fullUrl());

                return $panelUrl;
            }

            return (string) config('app.url');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
