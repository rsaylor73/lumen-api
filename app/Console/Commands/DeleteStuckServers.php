<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PendingServerQueue;
use App\Service\AwsService;
use App\Service\DnsService;
use App\Service\TestingServersService;
use App\Models\TestingServers;
use App\Models\EventLog;

class DeleteStuckServers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:delete-stuck-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The delete testing server that are stuck in the queue.';

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
        print "Running...\n";
        $data = TestingServers::findStuckServers();

        foreach ($data as $d) {
            $check = DnsService::checkIfDnsIsProtected($d->dns);
            if ($check === false) {
                // error
                EventLog::newEvent($d->id, "{$d->dns} is a protected sub domain. The delete has been halted.", "DeleteDNS");
                print "Error DNS!\n";
            }

            /*
                Leaving DNS for now as it most likely is already removed
                and limit risk of deleting valid DNS
            */

            /*
            TestingServersService::deleteDns($d->id, 'virtualjobshadow.com', $d->dns);
            TestingServersService::deleteDns($d->id, 'virtualjobshadow.com', $d->dns . ".admin");
            TestingServersService::deleteDns($d->id, 'virtualjobshadow.com', $d->dns . ".auth");
            TestingServersService::deleteDns($d->id, 'virtualjobshadow.com', $d->dns . ".planner");
            TestingServersService::deleteDns($d->id, 'vjsjunior.com', $d->dns);
            */

            if ($d->instanceID != "pending") {
                print "Remove instance {$d->instanceID}...\n";
                EventLog::newEvent($d->id, "{$d->dns} instance {$d->instanceID} was a stuck server. Deleting...", "error");
                AwsService::terminateInstance($d->instanceID);
            }
            PendingServerQueue::updateServerStatus($d->queueID, 'error');
            TestingServers::updateCurrentStatus($d->id, 'error');
            print "Marked server {$d->id} : {$d->dns} as error.\n";
        }

    }
}
