<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('warehouses')->insert([
            'name' => 'Minuman Saset',
            'purpose' => 'in',
        ]);
        DB::table('warehouses')->insert([
            'name' => 'Botol',
            'purpose' => 'in',
        ]);
        DB::table('warehouses')->insert([
            'name' => 'Toko',
            'purpose' => 'store',
        ]);
        DB::table('warehouses')->insert([
            'name' => 'Gudang Belakang Toko',
            'purpose' => 'in',
        ]);
        DB::table('warehouses')->insert([
            'name' => 'Gudang Barang Rusak',
            'purpose' => 'out'
        ]);
    }
}
