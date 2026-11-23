<?php

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

it('registers partner providers via the ModuleManagerServiceProvider glob', function () {
    expect(app()->getProvider(DummyPaymentServiceProvider::class))->not->toBeNull();
});

it('registers module providers via the ModuleManagerServiceProvider glob', function () {
    // UserServiceProvider is not listed in bootstrap/providers.php, so finding it
    // registered proves the auto-discovery works end to end.
    expect(app()->getProvider(UserServiceProvider::class))->not->toBeNull();
});
