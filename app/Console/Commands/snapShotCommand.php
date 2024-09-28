<?php

namespace App\Console\Commands;

use App\Models\PendingServerQueue;
use App\Models\SnapshotBackup;
use App\Models\SnapshotQueue;
use App\Service\AwsService;
use Illuminate\Console\Command;
use App\Models\TestingServers;

class snapShotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:snap-shot-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs AWS snapshot for testing servers in the backup queue.';

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
        $data = SnapshotQueue::pullPendingRequests();
        foreach ($data as $d) {

            $instanceID = $d->instanceID;
            if ($instanceID != "") {
                SnapshotQueue::updateServerStatus($d->id, 'running');
                $instance = AwsService::describeInstance($instanceID);
                if ($instance ==! false) {
                    if(isset($instance['Reservations'][0]['Instances'][0]['BlockDeviceMappings'][0]['Ebs']['VolumeId'])) {
                        $volumeID = $instance['Reservations'][0]['Instances'][0]['BlockDeviceMappings'][0]['Ebs']['VolumeId'];

                        $today = date("m-d-Y H:i:s");
                        $description = "VJS Test Server : Snapshot Backup : {$today} : {$d->ticketID}";
                        $snapshot = AwsService::createSnapShot($volumeID, $description);
                        if ($snapshot !== false) {
                            $snapshotID = $snapshot['SnapshotId'];
                            SnapshotBackup::saveSnapShot($d->ticketID, $snapshotID);
                            print "Backup running on {$d->ticketID}\n";
                            SnapshotQueue::updateServerStatus($d->id, 'complete');
                        } else {
                            print "Error: Snapshot failed to generate.\n";
                        }
                    } else {
                        print "Error! No volume to backup from.\n";
                    }
                }
            } else {
                print "Error! Invalid instance ID.\n";
            }
        }
    }
}
