<?php

use App\Modules\Users\Models\User;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
 * The per-user timezone resolver is registered once by UserServiceProvider
 * and is load-bearing for every panel: the shared Core Filament panel base
 * renders all timestamps through it.
 */

it('falls back to the application timezone for guests', function () {
    expect(FilamentTimezone::get())->toBe(config('app.timezone'));
});

it('resolves the authenticated user timezone through the registered resolver', function () {
    $user = User::factory()->create([
        'timezone' => 'America/Argentina/Buenos_Aires',
    ]);

    $this->actingAs($user);

    expect(FilamentTimezone::get())->toBe('America/Argentina/Buenos_Aires');
});

it('falls back to the application timezone when the user has none', function () {
    $user = User::factory()->create([
        'timezone' => null,
    ]);

    $this->actingAs($user);

    expect(FilamentTimezone::get())->toBe(config('app.timezone'));
});
