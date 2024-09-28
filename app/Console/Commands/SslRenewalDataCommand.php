<?php

namespace App\Console\Commands;

use App\Models\PendingServerQueue;
use App\Models\SnapshotQueue;
use Illuminate\Console\Command;
use App\Models\TestingServers;

class SslRenewalDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ssl-renewal-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates SSL renewal queue for Ansible.';

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
        $data = TestingServers::getSslRenewalForQueue();
        if(is_object($data)) {
            foreach ($data as $d) {
                SnapshotQueue::newSslQueueRequest($d->id);
            }
        }

        print "Done!\n";
    }
}
