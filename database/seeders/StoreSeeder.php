<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('stores')->insert([
            'name' => 'Toko1',
        ]);
        DB::table('stores')->insert([
            'name' => 'Toko2',
        ]);
        DB::table('stores')->insert([
            'name' => 'Toko3',
        ]);
        DB::table('stores')->insert([
            'name' => 'Toko4',
        ]);
        DB::table('stores')->insert([
            'name' => 'Toko5'
        ]);
    }
}
