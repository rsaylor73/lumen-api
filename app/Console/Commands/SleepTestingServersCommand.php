<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TestingServers;
use App\Models\PendingServerQueue;
use App\Service\AwsService;

class SleepTestingServersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:sleep-testing-servers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets testing servers to sleep after 72 hours.';

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
        $data = TestingServers::findServersToGoToSleep();

        if (!empty($data)) {
            foreach ($data as $d) {
                // shutdown instance

                $result = AwsService::shutDownInstance($d->instanceID);
                print "Shutting down {$d->id} : {$d->ticket}\n";
                if ($result !== false) {
                    PendingServerQueue::updateServerStatus($d->queueID, 'shutdown');
                    TestingServers::updateCurrentStatus($d->id, 'shutdown');
                } else {
                    print "============================\n";
                    print "== ERROR!                 ==\n";
                    print "============================\n";
                }
            }
        }
    }
}
