<?php

declare(strict_types=1);

require_once __DIR__.'/../Helpers/browser.php';

use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('signs an Admin in through the Admin panel to the dashboard', function () {
    $admin = User::factory()->admin()->create();

    loginThroughPanel($admin, 'admin')
        ->assertPathIs('/admin')
        ->assertNoJavaScriptErrors();
});

it('signs a SuperAdmin in through the SuperAdmin panel to the dashboard', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    loginThroughPanel($superAdmin, 'super-admin')
        ->assertPathIs('/super-admin')
        ->assertNoJavaScriptErrors();
});

it('rejects an Admin at the SuperAdmin login', function () {
    $admin = User::factory()->admin()->create();

    $page = submitPanelLogin('super-admin', $admin->email);

    waitForTextIn($page, 'body', 'These credentials do not match our records.')
        ->assertPathIs('/super-admin/login');
});
