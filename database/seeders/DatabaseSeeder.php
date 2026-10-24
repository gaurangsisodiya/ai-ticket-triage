<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default dataset for `php artisan db:seed` / `migrate:fresh --seed`.
        // To switch domain:  php artisan db:seed --class=ErpDemoSeeder  (or GovTechDemoSeeder)
        $this->call(GovTechDemoSeeder::class);
    }
}
