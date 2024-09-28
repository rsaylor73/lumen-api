<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingServersBuildLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $statusArray = (['Init', 'Building', 'SSL', 'DNS', 'Git', 'Software', 'MS-SQL', 'Deployed']);

        for ($i=0; $i < 100; $i++) {
            $testingID = rand(1, 86);

            $status = array_rand($statusArray, 1);

            DB::table('testing_servers_build_log')->insert([
                'ticketID' => $testingID,
                'status' => $statusArray[$status],
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ]);
        }
    }
}
