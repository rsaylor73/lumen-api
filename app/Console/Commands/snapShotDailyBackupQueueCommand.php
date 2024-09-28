<?php

namespace App\Console\Commands;

use App\Models\PendingServerQueue;
use App\Models\SnapshotBackup;
use App\Models\SnapshotQueue;
use App\Service\AwsService;
use Illuminate\Console\Command;
use App\Models\TestingServers;

class snapShotDailyBackupQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:snap-shot-daily-backup-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds testing servers to the queue.';

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
        $data = TestingServers::getDailyServersBackup();
        foreach ($data as $d) {
            SnapshotQueue::backup($d->id);
        }
    }
}
