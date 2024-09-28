<?php

namespace App\Console\Commands\v2;

use Illuminate\Console\Command;
use App\Models\v2\ServerQueue;

class PendingTestingServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2:pending-testing-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command to be ran every min to run any pending servers with Terraform.';

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
        $data = ServerQueue::getPendingServerList('pending');
        if (!empty($data)) {
            foreach ($data as $d) {
                ServerQueue::updateStatus($d->id, 'running');
                $cmd = "cd /home/ubuntu/Terraform/testing-servers && bash cli/build_testing_server.sh {$d->dns} {$d->terraform_variable_string} {$d->email}";
                system($cmd);

                sleep(1800); /* 30 minutes */
                $cmd = "cd /home/ubuntu/Terraform/testing-servers && bash cli/update_testing_server_security_group.sh {$d->dns} {$d->terraform_variable_string}";
                system($cmd);
                ServerQueue::updateStatus($d->id, 'complete');
            }
        }

        return true;
    }
}
