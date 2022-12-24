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
            'name' => 'BISCUIT'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'SNACK'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'PERMEN'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'MIE INSTANT'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'SUSU'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'MINUMAN'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'MINUMAN BOTOL'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'MINYAK'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'BERAS'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'GARAM'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'TISSUE'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'PLASTIC'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'TAS KONDANGAN'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'KOPI'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'MAINAN'
        ]);
        DB::table('category_Items')->insert([
            'name' => 'JELLY'
        ]);
    }
}
