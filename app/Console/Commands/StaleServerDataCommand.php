<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TestingServers;
use App\Models\StaleServer;

class StaleServerDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:stale-server-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates stale server data.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $baseDate = "2021-09-01"; // estimated when the API went online
        $date1 = new \DateTime("$baseDate");
        $date2 = new \DateTime();
        $date2->modify('-30 days');

        $data = TestingServers::locatePossibleStaleServers($date1, $date2);
        if (is_object($data)) {
            foreach ($data as $d) {
                if (is_null($d->staleID)) {
                    StaleServer::newStaleServer($d->id);
                    print "Server added...\n";
                }
            }
        }
        print "Done\n";
    }
}
