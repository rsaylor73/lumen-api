<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PendingServerQueueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        for ($i=0; $i < 3; $i++) {
            $testingID = rand(1, 86);

            DB::table('pending_server_queue')->insert([
                'ticketID' => $testingID,
                'status' => 'pending',
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ]);
        }
    }
}
