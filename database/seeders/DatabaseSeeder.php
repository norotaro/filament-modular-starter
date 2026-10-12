<?php

namespace Database\Seeders;

use App\Modules\Users\Database\Seeders\UserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Module seeders live inside their modules; this root seeder only
     * aggregates them in FK-safe order. The skeleton has one entry.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
    }
}
