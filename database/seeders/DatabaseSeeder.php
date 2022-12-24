<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UsersSeeder::class,
            PriceCategoriesSeeder::class,
            CategoryItemsSeeder::class,
            ItemsSeeder::class,
            UnitsSeeder::class,
            WarehouseSeeder::class
        ]);
    }
}
