<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RestoreSnapshotServerQueue;
use App\Models\PendingServerQueue;
use App\Service\BackendCommandService;
use App\Service\AwsService;

class RestoreSnapshotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:restore-snapshot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restores pending snapshots flagged for restore.';

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
        $data = RestoreSnapshotServerQueue::getPendingServerList('pending');
        if (!empty($data)) {
            foreach ($data as $d) {
                $snapshot = $d->snapshotID;

                /* Lookup snapshot and validate it is ready and available... */
                $status = AwsService::describeSnapshot($snapshot);
                $progress = "";
                if ($status !== false) {
                    $progress = $status['Snapshots'][0]['Progress'];
                } else {
                    // The snapshot has an error so we will silently fail
                    RestoreSnapshotServerQueue::updateServerStatus($d->id, 'error');
                }

                if ($progress != "100%") {
                    print "Snapshot is not ready the job will be picked up in the next cron...\n";

                }
                print "Snapshot Progress: {$progress}\n";
                $cmd = "";

                if ($progress == "100%") {
                    if ($d->clone_flag == "1") {
                        $cmd = BackendCommandService::generateRestoreCloneSnapshotAnsibleCommand($d, $snapshot);
                    } else {
                        $cmd = BackendCommandService::generateRestoreSnapshotAnsibleCommand($d, $snapshot);
                    }
                    RestoreSnapshotServerQueue::updateServerStatus($d->id, 'complete');

                    /* Update build status */
                    PendingServerQueue::updateServerStatus($d->ticketID, 'building');

                    print "Running Ansible...\n";
                    system($cmd);
                }
            }
        }
    }
}
