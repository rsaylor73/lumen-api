<?php

namespace App\Console\Commands\v2;

use Illuminate\Console\Command;
use App\Models\v2\DeleteDemoServerQueue;
use App\Models\v2\DemoServerQueue;

class DeleteDemoServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2:delete-demo-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes a demo server managed by Terraform.';

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
        $data = DeleteDemoServerQueue::getServerList('pending');
        if (!empty($data)) {
            foreach ($data as $d) {
                DeleteDemoServerQueue::updateStatus($d->id, 'running');

                $cmd = "cd /home/ubuntu/Terraform/testing-servers && bash cli/delete_server.sh {$d->dns}";
                system($cmd);

                DeleteDemoServerQueue::updateStatus($d->id, 'complete');
                $qId = DemoServerQueue::getServerQueueId($d->demo_serverID);

                DemoServerQueue::updateStatus($qId[0]->id, 'terminated');
            }
        }
    }
}
