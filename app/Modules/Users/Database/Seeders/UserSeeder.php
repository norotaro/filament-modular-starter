<?php

namespace App\Modules\Users\Database\Seeders;

use App\Modules\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed a Super Admin and an Admin for local development.
     */
    public function run(): void
    {
        User::factory()
            ->superAdmin()
            ->create([
                'name' => 'Super Admin',
                'email' => 'super-admin@example.test',
                'password' => Hash::make('password'),
            ]);

        User::factory()
            ->admin()
            ->create([
                'name' => 'Admin User',
                'email' => 'admin@example.test',
                'password' => Hash::make('password'),
            ]);
    }
}
