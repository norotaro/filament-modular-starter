<?php

use App\Modules\Example\ExampleServiceProvider;
use App\Modules\Users\UserServiceProvider;
use App\Partners\DummyPayments\DummyPaymentServiceProvider;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('boots the application and serves the health route', function () {
    $this->get('/up')->assertSuccessful();
});

it('redirects the root route to the default panel', function () {
    $this->get('/')->assertRedirect('/admin');
});

it('registers module providers via the ModuleManagerServiceProvider glob', function () {
    // ExampleServiceProvider matches the one-level glob shape and is NOT
    // listed in bootstrap/providers.php — this assertion is what proves the
    // auto-discovery works end to end.
    expect(app()->getProvider(ExampleServiceProvider::class))->not->toBeNull();
});

it('registers partner providers via the ModuleManagerServiceProvider glob', function () {
    expect(app()->getProvider(DummyPaymentServiceProvider::class))->not->toBeNull();
});

it('registers the Users module provider through the same glob', function () {
    expect(app()->getProvider(UserServiceProvider::class))->not->toBeNull();
});
