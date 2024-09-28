<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HardRebootServerQueue;
use Symfony\Component\Config\Definition\Exception\Exception;

class Ec2HardRebootQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ec2-hard-reboot-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue to hard reboot any AWS EC2.';

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
        $data = HardRebootServerQueue::getPendingHardRebootList('pending');
        if (!empty($data)) {
            foreach ($data as $d) {
                HardRebootServerQueue::updateServerStatus($d->id, 'complete');
                $cmd = "php artisan command:ec2-degrade-restore {$d->instanceID}";
                system($cmd);
            }
        }
    }
}
