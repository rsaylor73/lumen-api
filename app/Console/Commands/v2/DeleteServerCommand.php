<?php

namespace App\Console\Commands\v2;

use Illuminate\Console\Command;
use App\Models\v2\DeleteServerQueue;
use App\Models\v2\ServerQueue;

class DeleteServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2:delete-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes a server managed by Terraform.';

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
        $data = DeleteServerQueue::getServerList('pending');
        if (!empty($data)) {
            foreach ($data as $d) {
                DeleteServerQueue::updateStatus($d->id, 'running');

                $cmd = "cd /home/ubuntu/Terraform/testing-servers && bash cli/delete_server.sh {$d->dns}";
                system($cmd);

                DeleteServerQueue::updateStatus($d->id, 'complete');
                $qId = ServerQueue::getServerQueueId($d->testing_serverID);

                ServerQueue::updateStatus($qId[0]->id, 'terminated');
            }
        }
    }
}
