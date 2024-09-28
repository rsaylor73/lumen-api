<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PendingServerQueue;
use App\Service\AwsService;
use App\Service\DnsService;
use App\Service\TestingServersService;
use App\Models\TestingServers;
use App\Models\DeleteTestingServer;
use App\Models\EventLog;

class DeleteTestingServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:delete-testing-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The delete testing server will run every 15 minutes to delete a testing server in the delete queue.';

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
        print "Running...\n";
        $data = DeleteTestingServer::getDeleteServers('pending');
        foreach ($data as $d) {
            $server = TestingServers::getSingleServer($d->ticketID);
            foreach ($server as $s) {
                $check = DnsService::checkIfDnsIsProtected($s->dns);
                if ($check === false) {
                    // error : set the server as deleted no further process

                    die;
                }
                $instance = AwsService::describeInstance($s->instanceID);

                if ($instance ==! false) {
                    if(!isset($instance['Reservations'][0]['Instances'][0]['BlockDeviceMappings'][0]['Ebs']['VolumeId'])) {
                        print "Error....\n";

                        TestingServersService::deleteDns($s->dns);

                        PendingServerQueue::updateServerStatus($s->queueID, 'error');
                        TestingServers::updateCurrentStatus($s->id, 'error');
                        DeleteTestingServer::updateServerStatus($d->id, 'error');

                        /* Delete server logs */
                        $cmd = "cd /var/www/html/log && rm -f server-{$d->ticketID}.log";
                        system($cmd);
                        $cmd = "cd /var/www/html/log && rm -f server-{$d->ticketID}-*";
                        system($cmd);

                    } else {
                        $volumeID = $instance['Reservations'][0]['Instances'][0]['BlockDeviceMappings'][0]['Ebs']['VolumeId'];

                        /* Create Snapshot */
                        $today = date("m-d-Y H:i:s");
                        $description = "VJS Test Server : {$today} : {$s->id} : {$s->ticket}";
                        $snapshot = AwsService::createSnapShot($volumeID, $description);
                        if ($snapshot !== false) {
                            $snapshotID = $snapshot['SnapshotId'];

                            /* Record Snapshot ID */
                            TestingServers::updateSnapshotId($d->ticketID, $snapshotID);
                            sleep(45); // Allow some time for the snapshot to run

                            TestingServersService::deleteDns($s->dns);

                            $ec2 = AwsService::terminateInstance($s->instanceID);
                            if ($ec2 === false) {
                                EventLog::newEvent($d->ticketID, 'Failed to terminate', 'EC2');

                                // Update server status
                                PendingServerQueue::updateServerStatus($s->queueID, 'error');
                                TestingServers::updateCurrentStatus($s->id, 'error');
                            }

                            EventLog::newEvent($d->ticketID, 'Server terminated', 'EC2');

                            // Update server status
                            PendingServerQueue::updateServerStatus($s->queueID, 'terminated');
                            TestingServers::updateCurrentStatus($s->id, 'terminated');

                            sleep(90); // Allow some time for the server to delete

                            /* Delete server logs */
                            $cmd = "cd /var/www/html/log && rm -f server-{$d->ticketID}.log";
                            system($cmd);
                            $cmd = "cd /var/www/html/log && rm -f server-{$d->ticketID}-*";
                            system($cmd);

                            /* Delete security group */
                            $cmd = "aws ec2 delete-security-group --group-id {$d->security_groupID}";
                            system($cmd);

                            DeleteTestingServer::updateServerStatus($d->id, 'complete');
                            print "Updated {$d->id}.\n";
                        } else {
                            // error
                            print "Error....\n";

                            TestingServersService::deleteDns($s->dns);

                            PendingServerQueue::updateServerStatus($s->queueID, 'error');
                            TestingServers::updateCurrentStatus($s->id, 'error');
                            DeleteTestingServer::updateServerStatus($d->id, 'error');
                        }
                    }
                } else {
                    // Mark error
                    print "Error....\n";

                    TestingServersService::deleteDns($s->dns);

                    PendingServerQueue::updateServerStatus($s->queueID, 'error');
                    TestingServers::updateCurrentStatus($s->id, 'error');
                    DeleteTestingServer::updateServerStatus($d->id, 'error');
                }
            }
        }
        print "Done.\n";
    }
}
