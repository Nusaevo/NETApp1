<?php

namespace Database\Seeders;

use App\Models\ConfigUser;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Generator $faker)
    {
        // Create a demo user
        ConfigUser::create([
            'code'    => 'andryhuang',
            'password'    => Hash::make('secret'),
            'name'    => $faker->name,
            'dept'         => 'Demo Department',
            'phone'        => $faker->phoneNumber,
            'email'        => 'demo@demo.com',
            'status_code'  => 'A',
            'created_by'   => "SYSTEM",
            'updated_by'   => "SYSTEM",
            'version_number' => 1,
        ]);

        // Create additional demo users
        for ($i = 0; $i < 9; $i++) {
            ConfigUser::create([
                'code'    => $faker->unique()->userName,
                'password'    => Hash::make('secret'),
                'name'    => $faker->name,
                'dept'         => $faker->word,
                'phone'        => $faker->phoneNumber,
                'email'        => $faker->unique()->safeEmail,
                'status_code'  => 'A',
                'created_by'   => "SYSTEM",
                'updated_by'   => "SYSTEM",
                'version_number' => 1,
            ]);
        }
    }
}
