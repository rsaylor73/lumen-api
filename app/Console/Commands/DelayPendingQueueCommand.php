<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DelayDnsLog;
use App\Models\PendingServerQueue;
use App\Models\EventLog;
use App\Service\BackendCommandService;

class DelayPendingQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:delay-pending-servers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for any servers in the delay status and move to pending.';

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
        $time = date("U");

        $data = DelayDnsLog::getDelayData();
        if (!empty($data)) {
            foreach ($data as $d) {
                $time_to_live = $d->time_to_live;
                if ($time_to_live < $time) {
                    // set queue to pending
                    PendingServerQueue::updateServerStatus($d->queueID, 'pending');
                    DelayDnsLog::updateDelayStatus($d->id, 'complete');
                }
            }
        }
        return 1;
    }
}
