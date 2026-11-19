<?php

declare(strict_types=1);

use App\Modules\Core\Support\Route as PanelRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * A pinned domain must not outlive the call: a long-lived process (queue
 * worker, Octane, the test runner) reuses the URL generator, and a pin left
 * behind sends every later route() to the first call's host.
 */
it('lets the next request generate URLs on its own host after a pinned link', function (Closure $mint) {
    $mint();

    app('url')->setRequest(Request::create('http://next.test/'));

    expect(route('filament.admin.auth.login'))->toBe('http://next.test/admin/login');
})->with([
    'signed link' => [fn () => PanelRoute::toPanelSignedRoute('filament.admin.auth.login', [], 'pinned.test')],
    'plain link' => [fn () => PanelRoute::toPanelRoute('filament.admin.auth.login', [], 'pinned.test')],
]);

it('pins the minted link itself to the requested domain', function () {
    $signed = PanelRoute::toPanelSignedRoute('filament.admin.auth.login', [], 'pinned.test');

    expect(parse_url($signed, PHP_URL_HOST))->toBe('pinned.test')
        ->and(Request::create($signed)->hasValidSignature())->toBeTrue()
        ->and(PanelRoute::toPanelRoute('filament.admin.auth.login', [], 'pinned.test'))->toBe('https://pinned.test/admin/login');
});

it('uses the current origin when no domain is given', function () {
    app('url')->setRequest(Request::create('http://current.test/'));

    expect(PanelRoute::toPanelRoute('filament.admin.auth.login'))->toBe('http://current.test/admin/login');
});

it('signs for two days by default and honors an explicit expiry', function () {
    Carbon::setTestNow('2026-01-01 12:00:00');

    $expires = fn (string $url) => (int) Request::create($url)->query('expires');

    expect($expires(PanelRoute::toPanelSignedRoute('filament.admin.auth.login')))
        ->toBe(Carbon::now()->addDays(2)->getTimestamp())
        ->and($expires(PanelRoute::toPanelSignedRoute('filament.admin.auth.login', expiresAt: Carbon::now()->addHours(3))))
        ->toBe(Carbon::now()->addHours(3)->getTimestamp());
});

it('refuses to sign with an empty app.key', function () {
    config()->set('app.key', '');

    expect(fn () => PanelRoute::toPanelSignedRoute('filament.admin.auth.login'))
        ->toThrow(InvalidArgumentException::class, 'non-empty');
});
