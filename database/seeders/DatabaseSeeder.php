<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * `php artisan migrate --seed` : uniquement les données de référence
 * (rôles, permissions, réglages). Les données de démonstration sont dans
 * DemoSeeder, refusé hors environnement local.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReferenceSeeder::class);
    }
}
