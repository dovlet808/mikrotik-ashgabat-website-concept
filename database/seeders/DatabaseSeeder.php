<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds the catalogue from the supplied source material. Administrator
     * accounts are never seeded: create one with `php artisan app:create-admin`.
     */
    public function run(): void
    {
        $this->call(CatalogSeeder::class);
    }
}
