<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigMenusTableSeeder extends Seeder
{
    public function run()
    {
        // Sample data to insert
        $menus = [
            [
                'code' => 'menu1',
                'appl_id' => '1',
                'appl_code' => 'app1',
                'menu_header' => 'Setting',
                'sub_menu' => '',
                'menu_caption' => 'User',
                'link' => 'config_users',
                'status_code' => 'A'
            ],
            [
                'code' => 'menu2',
                'appl_id' => '1',
                'appl_code' => 'app1',
                'menu_header' => 'Setting',
                'sub_menu' => '',
                'menu_caption' => 'Group',
                'link' => 'config_groups',
                'status_code' => 'A'
            ],
            [
                'code' => 'menu3',
                'appl_id' => '1',
                'appl_code' => 'app1',
                'menu_header' => 'Setting',
                'sub_menu' => '',
                'menu_caption' => 'Application',
                'link' => 'config_applications',
                'status_code' => 'A'
            ],
            [
                'code' => 'menu4',
                'appl_id' => '1',
                'appl_code' => 'app1',
                'menu_header' => 'Setting',
                'sub_menu' => '',
                'menu_caption' => 'Menu',
                'link' => 'config_menus',
                'status_code' => 'A'
            ],
            [
                'code' => 'menu5',
                'appl_id' => '1',
                'appl_code' => 'app1',
                'menu_header' => 'Setting',
                'sub_menu' => '',
                'menu_caption' => 'Rights',
                'link' => 'config_rights',
                'status_code' => 'A'
            ],
            [
                'code' => 'menu6',
                'appl_id' => '2',
                'appl_code' => 'app2',
                'menu_header' => 'Menu Header 2',
                'sub_menu' => 'Sub Menu 2',
                'menu_caption' => 'Menu Caption 2',
                'link' => 'test',
                'status_code' => 'A'
            ],
        ];

        // Insert data into the config_menus table
        DB::table('config_menus')->insert($menus);
    }
}
