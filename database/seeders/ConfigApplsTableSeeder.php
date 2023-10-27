<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigApplsTableSeeder extends Seeder
{
    public function run()
    {
        // Sample data to insert
        $applications = [
            [
                'code' => 'app1',
                'name' => 'Application Master',
                'version' => '1.0',
                'descr' => 'This application is for config purpose',
                'status_code' => 'A',
            ],
            [
                'code' => 'app2',
                'name' => 'Pos 2',
                'version' => '1.0.0',
                'descr' => 'Test',
                'status_code' => 'A',
            ],
            // Add more application data as needed
        ];

        // Insert data into the config_appls table
        DB::table('config_appls')->insert($applications);
    }
}
