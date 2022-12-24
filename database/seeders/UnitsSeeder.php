<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('units')->insert([
            'name' => 'PCS',
        ]);
        DB::table('units')->insert([
            'name' => 'DUS',
        ]);
        DB::table('units')->insert([
            'name' => 'BAL',
        ]);
        DB::table('units')->insert([
            'name' => 'IKAT',
        ]);
        DB::table('units')->insert([
            'name' => 'BOX',
        ]);
        DB::table('units')->insert([
            'name' => 'SLOP',
        ]);
        DB::table('units')->insert([
            'name' => 'PAK',
        ]);
        DB::table('units')->insert([
            'name' => 'GLANGSING (GSG)',
        ]);
        DB::table('units')->insert([
            'name' => 'RENTENG (RTG)',
        ]);
        DB::table('units')->insert([
            'name' => 'TOPLES'
        ]);
        DB::table('units')->insert([
            'name' => 'KALENG'
        ]);
        DB::table('units')->insert([
            'name' => 'GALON'
        ]);
    }
}
