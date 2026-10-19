<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GovernorateSeeder::class,
            CatalogSeeder::class,
            FinderSeeder::class,
            SettingSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
