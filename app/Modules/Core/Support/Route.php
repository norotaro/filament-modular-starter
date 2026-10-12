<?php

namespace App\Modules\Core\Support;

use Illuminate\Support\Facades\URL;

/**
 * Panel URL helpers.
 *
 * Generates absolute URLs for Filament panels, optionally forcing the origin
 * to a specific bare domain (useful when a future tenancy layer stores one
 * domain per tenant). The skeleton itself is single-domain: `app.url` is used
 * unless a `$domain` is passed explicitly.
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
        return self::withOrigin($domain, fn () => route($routeName, $params));
    }

    /**
     * Temporary signed URL for a panel route, valid for two days.
     *
     * @param  array<string, mixed>  $params
     */
    public static function toPanelSignedRoute(string $routeName, array $params = [], ?string $domain = null): string
    {
        return self::withOrigin($domain, fn () => URL::temporarySignedRoute($routeName, now()->addDays(2), $params));
    }

    private static function withOrigin(?string $domain, \Closure $callback): string
    {
        $currentOrigin = request()->schemeAndHttpHost();

        if ($domain !== null) {
            URL::useOrigin('https://'.$domain);
        }

        try {
            return $callback();
        } finally {
            URL::useOrigin($currentOrigin);
        }
    }
}
