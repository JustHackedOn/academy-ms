<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Demo data is intentionally excluded. Run it on demand with:
     *   php artisan db:seed --class=DemoDataSeeder
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
    }
}
