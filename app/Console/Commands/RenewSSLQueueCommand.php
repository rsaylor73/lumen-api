<?php

namespace App\Console\Commands;

use App\Models\EventLog;
use App\Models\TestingServers;
use App\Service\ZeroSSLService;
use Illuminate\Console\Command;
use App\Models\RenewSslQueue;
use App\Service\TestingServersService;
use App\Service\BackendCommandService;

class RenewSSLQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:renew-ssl-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitors the SSL renew queue.';

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
        $data = RenewSslQueue::pendingSSLRequests();
        if (!empty($data)) {
            foreach ($data as $d) {

                RenewSslQueue::updateCurrentStatus($d->id, 'running');

                //$cmd = "cd /home/ubuntu/Ansible && ansible-playbook playbook-renew-letsentrypt.yml --extra-vars \"sg={$d->security_groupID} ip={$d->ip_address} instance={$d->instanceID} email={$d->email} dns={$d->dns} sendgrid=TBD\"";
                $cmd = BackendCommandService::generateSSLRenewCommand($d);
                system($cmd);

                RenewSslQueue::updateCurrentStatus($d->id, 'complete');
            }
        }
    }
}
