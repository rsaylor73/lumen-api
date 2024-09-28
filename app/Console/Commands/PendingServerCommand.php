<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PendingServerQueue;
use App\Models\EventLog;
use App\Service\BackendCommandService;

class PendingServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:pending-servers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command to be ran every min to run any pending servers with Ansible.';

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
        $data = PendingServerQueue::getPendingServerList('pending');
        if (!empty($data)) {
            foreach ($data as $d) {
                if ($d->clone_flag == "1") {
                    $cmd = BackendCommandService::generateCloneDevAnsibleCommand($d);
                } else {
                    if ($d->ssh_flag == "1") {
                        $cmd = BackendCommandService::generateAnsibleCommandNoSSH($d);
                    } else {
                        $cmd = BackendCommandService::generateAnsibleCommand($d);
                    }
                }
                EventLog::newEvent($d->id, "New server building", 'Building');
                PendingServerQueue::updateServerStatus($d->id, 'building');

                /** Ansible will report when the build completes and update the status. */
                print "Running Ansible...\n";
                system($cmd);
            }
        }

        return 1;
    }
}
