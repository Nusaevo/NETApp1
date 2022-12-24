<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PriceCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('price_categories')->insert([
            'name' => 'Kategori 1'
        ]);
        DB::table('price_categories')->insert([
            'name' => 'Kategori 2'
        ]);
        DB::table('price_categories')->insert([
            'name' => 'Kategori 3'
        ]);
    }
}
