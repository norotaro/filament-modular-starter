<?php

namespace App\Modules\Core\Support;

use DateTimeInterface;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;

/**
 * Panel URL helpers.
 *
 * Generates absolute URLs for Filament panels, optionally forcing the origin
 * to a specific bare domain (useful when an application stores one domain per
 * tenant). Without a `$domain`, URLs use `app.url`.
 */
final readonly class Route
{
    /**
     * Absolute URL for a panel route, pinned to $domain's https origin when given.
     *
     * @param  array<string, mixed>  $params
     */
    public static function toPanelRoute(string $routeName, array $params = [], ?string $domain = null): string
    {
        return self::withOrigin($domain, fn (UrlGenerator $url) => $url->route($routeName, $params));
    }

    /**
     * Temporary signed URL for a panel route, valid for two days unless
     * $expiresAt says otherwise.
     *
     * Laravel signs happily with an empty `app.key`, and a signature made with
     * a public key is forgeable by anyone, so an empty key is refused here
     * just as RedirectUrlSigner refuses one.
     *
     * @param  array<string, mixed>  $params
     */
    public static function toPanelSignedRoute(
        string $routeName,
        array $params = [],
        ?string $domain = null,
        ?DateTimeInterface $expiresAt = null,
    ): string {
        if (trim((string) Config::get('app.key')) === '') {
            throw new InvalidArgumentException(
                'Route::toPanelSignedRoute() requires a non-empty app.key; an empty key makes signatures forgeable.',
            );
        }

        return self::withOrigin(
            $domain,
            fn (UrlGenerator $url) => $url->temporarySignedRoute($routeName, $expiresAt ?? now()->addDays(2), $params),
        );
    }

    /**
     * Pins the origin on a clone, never on the shared generator: forcing it
     * globally outlives the call in any long-lived process (a queue worker,
     * Octane, the test runner) and sends every later `route()` to this domain.
     *
     * @param  \Closure(UrlGenerator): string  $callback
     */
    private static function withOrigin(?string $domain, \Closure $callback): string
    {
        $url = URL::getFacadeRoot();

        if ($domain === null) {
            return $callback($url);
        }

        // A bare clone shares the original's RouteUrlGenerator, which formats
        // against the original and ignores the pin; setRequest() rebuilds it
        // on the clone and keeps the route default parameters.
        $pinned = clone $url;
        $pinned->setRequest($url->getRequest());
        // useOrigin() pins only the host; without forceScheme() the link keeps
        // the current request's scheme, so an http worker would mint http links.
        $pinned->forceScheme('https');
        $pinned->useOrigin('https://'.$domain);

        return $callback($pinned);
    }
}
