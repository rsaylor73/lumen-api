<?php

namespace App\Console\Commands\v2;

use App\Models\v2\DemoServers;
use Illuminate\Console\Command;
use App\Models\v2\DemoServerQueue;
use \App\Models\v2\DeleteDemoServerQueue;

class AutoDeleteDemoServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2:auto-delete-demo-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes a demo server managed by Terraform after XX days.';

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
        $days = "4"; /* set this to set the number of days a demo server should be active */

        $date = new \DateTime(); // For today/now, don't pass an arg.
        $date->modify("-{$days} day");
        echo $date->format("Y-m-d");

        print "Looking for servers created on {$date->format("Y-m-d")}:\n\n";
        $data = DemoServers::serversToDelete($date->format("Y-m-d"));
        foreach ($data as $d) {
            DeleteDemoServerQueue::newDeleteQueue($d->id, 'pending');
            print "Adding server {$d->id} to be deleted.\n";
        }
    }
}
