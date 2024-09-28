<?php

namespace App\Console\Commands\v2;

use Illuminate\Console\Command;
use App\Models\v2\DemoServerQueue;

class PendingDemoServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2:pending-demo-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command to be ran every min to run any pending demo servers with Terraform.';

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
        $data = DemoServerQueue::getPendingServerList('pending');
        if (!empty($data)) {
            foreach ($data as $d) {
                DemoServerQueue::updateStatus($d->id, 'running');
                $cmd = "cd /home/ubuntu/Terraform/testing-servers && bash cli/build_demo_server.sh {$d->dns} {$d->terraform_variable_string} {$d->email}";
                system($cmd);

                sleep(1800); /* 30 minutes */
                $cmd = "cd /home/ubuntu/Terraform/testing-servers && bash cli/update_demo_server_security_group.sh {$d->dns} {$d->terraform_variable_string}";
                system($cmd);
                DemoServerQueue::updateStatus($d->id, 'complete');
            }
        }

        return true;
    }
}
