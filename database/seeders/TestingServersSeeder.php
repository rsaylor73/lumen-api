<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestingServersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 0; $i < 100; $i++) {
            DB::table('testing_servers')->insert([
                'ticket' => 'dev-' . rand(1000, 9000),
                'dns' => Str::random(20),
                'email' => Str::random(20) . '@strivven.com',
                'ip_address' => rand(100, 199) . "." . rand(40, 90) . "." . rand(100, 200) . "." . rand(180, 255),
                'instanceID' => "i-" . rand(400000, 900000),
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ]);
        }
    }
}
