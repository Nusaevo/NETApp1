<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigGroupsTableSeeder extends Seeder
{
    public function run()
    {
        // Sample data to insert
        $applications = [
            // [
            //     'code' => 'Admin01',
            //     'appl_id' => '1',
            //     'appl_code' => 'app1',
            //     'name' => 'Super Admin',
            //     'status_code' => 'A',
            // ],
            // [
            //     'code' => 'Admin02',
            //     'appl_id' => '2',
            //     'appl_code' => 'app2',
            //     'name' => 'Admin',
            //     'status_code' => 'A',

            // ],
            // [
            //     'code' => 'User01',
            //     'appl_id' => '1',
            //     'appl_code' => 'app1',
            //     'name' => 'Normal User',
            //     'status_code' => 'A',
            // ],
            // Add more application data as needed
        ];

        // Insert data into the config_appls table
        DB::table('config_groups')->insert($applications);
    }
}
