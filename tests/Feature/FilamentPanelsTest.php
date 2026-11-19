<?php

it('loads the Admin panel login page', function () {
    $this->withoutVite()
        ->get('/admin/login')
        ->assertSuccessful();
});

it('loads the SuperAdmin panel login page', function () {
    $this->withoutVite()
        ->get('/super-admin/login')
        ->assertSuccessful();
});

it('registers both panel route namespaces', function () {
    $names = collect(app('router')->getRoutes()->getRoutesByName())->keys();

    expect($names->contains('filament.admin.pages.dashboard'))->toBeTrue()
        ->and($names->contains('filament.super-admin.pages.dashboard'))->toBeTrue();
});
