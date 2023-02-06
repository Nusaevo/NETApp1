<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoryItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('category_Items')->insert([
            'name' => 'Elektronik'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'Fashion'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'Rumah Tangga'
        ]);
    }
}
