<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DemoServersQueue;
use App\Service\BackendCommandService;

class PendingDemoServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:pending-demo-servers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command to be ran every min to run any pending demo servers with Ansible.';

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
        $data = DemoServersQueue::getPendingServerList('pending');
        if (!empty($data)) {
            foreach ($data as $d) {
                $cmd = BackendCommandService::generateDemoServerAnsibleCommand($d);

                DemoServersQueue::updateServerStatus($d->id, 'building');

                /** Ansible will report when the build completes and update the status. */
                print "Running Ansible...\n\n";
                system($cmd);
            }
        }

        return 1;
    }
}
