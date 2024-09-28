<?php

namespace App\Console\Commands;

use App\Models\DeleteTestingServer;
use App\Models\EventLog;
use App\Service\DnsService;
use Illuminate\Console\Command;
use App\Models\TestingServers;
use App\Models\PendingServerQueue;
use App\Service\AwsService;

class SleepTestingServerTerminateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:sleep-testing-servers-terminate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes testing servers sleeping about 7 days.';

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
        $data = TestingServers::findServersToTerminate();
        if (!empty($data)) {
            foreach ($data as $d) {
                // terminate instance
                print "Terminating {$d->id} : {$d->ticket}\n";

                $check = DnsService::checkIfDnsIsProtected($d->dns);
                if ($check === false) {
                    // error
                    EventLog::newEvent($d->id, "{$d->dns} is a protected sub domain. The delete has been halted.", "DeleteDNS");
                    print "Error DNS!\n";
                }

                DeleteTestingServer::newDeletServer($d->id);
                PendingServerQueue::updateServerStatus($d->queueID, 'terminated');
                TestingServers::updateCurrentStatus($d->id, 'terminated');
                print "Deleted {$d->id}\n";
            }
        }

    }
}
