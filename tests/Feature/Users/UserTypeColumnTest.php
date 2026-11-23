<?php

use App\Modules\Users\Enums\UserType;
use App\Modules\Users\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

function userRowWithoutType(): array
{
    return [
        'name' => 'No Role',
        'email' => 'no-role@example.test',
        'password' => 'hashed',
        'created_at' => now(),
        'updated_at' => now(),
    ];
}

test('a user row without an explicit type is rejected', function () {
    expect(fn () => DB::table('users')->insert(userRowWithoutType()))
        ->toThrow(QueryException::class);

    expect(DB::table('users')->count())->toBe(0);
});

test('a user row with an explicit type is stored and cast to its role', function () {
    DB::table('users')->insert(userRowWithoutType() + ['type' => UserType::Admin->value]);

    expect(User::query()->sole()->type)->toBe(UserType::Admin);
});
