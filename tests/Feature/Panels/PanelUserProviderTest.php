<?php

declare(strict_types=1);

use App\Modules\Users\Filament\Pages\Login;
use App\Modules\Users\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['email' => 'admin@example.test']);
    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'root@example.test']);
});

function lookUp(string $email): ?User
{
    $user = Auth::createUserProvider('users')->retrieveByCredentials(['email' => $email, 'password' => 'secret']);

    return $user instanceof User ? $user : null;
}

it('lets each panel find only its own role', function () {
    Filament::setCurrentPanel('admin');

    expect(lookUp('admin@example.test')?->is($this->admin))->toBeTrue()
        ->and(lookUp('root@example.test'))->toBeNull();

    Filament::setCurrentPanel('super-admin');

    expect(lookUp('root@example.test')?->is($this->superAdmin))->toBeTrue()
        ->and(lookUp('admin@example.test'))->toBeNull();
});

it('finds nobody by credentials when no panel is current', function () {
    Filament::setCurrentPanel(null);

    expect(lookUp('admin@example.test'))->toBeNull()
        ->and(lookUp('root@example.test'))->toBeNull();
});

it('reads the current panel at lookup time, not when the provider was built', function () {
    Filament::setCurrentPanel('admin');
    $provider = Auth::createUserProvider('users');

    Filament::setCurrentPanel('super-admin');

    expect($provider->retrieveByCredentials(['email' => 'admin@example.test']))->toBeNull()
        ->and($provider->retrieveByCredentials(['email' => 'root@example.test'])?->is($this->superAdmin))->toBeTrue();
});

it('still restores a signed-in user by id outside a panel', function () {
    Filament::setCurrentPanel(null);

    expect(Auth::createUserProvider('users')->retrieveById($this->admin->getKey())?->is($this->admin))->toBeTrue();
});

it('signs each role in through its own panel login and rejects the other role', function () {
    Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(Login::class)
        ->fillForm(['email' => 'root@example.test', 'password' => 'password'])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);
    expect(Auth::check())->toBeFalse();

    Livewire\Livewire::test(Login::class)
        ->fillForm(['email' => 'admin@example.test', 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();
    expect(Auth::user()?->is($this->admin))->toBeTrue();
});
