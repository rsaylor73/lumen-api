<?php

namespace App\Console\Commands;

use App\Models\PendingServerQueue;
use Illuminate\Console\Command;
use App\Service\AwsService;
use App\Service\TestingServersService;
use App\Service\DnsService;
use App\Models\TestingServers;
use App\Models\DemoServers;
use Symfony\Component\Config\Definition\Exception\Exception;

class Ec2DegradeRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ec2-degrade-restore {instanceID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will move a degraded AWS EC2 to operational hardware. Must pass in the instanceID.';

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
        $instanceID = $this->argument('instanceID');

        /* Step 1 : Get ServerID (Testing Server or Demo Server */
        $testing = TestingServers::findByInstanceId($instanceID);
        $demo = DemoServers::findByInstanceId($instanceID);

        if ($testing->isNotEmpty()) {
            print "Testing Server:\n";
            foreach ($testing as $t) {
                print "DNS: {$t->dns}\n";
                print "Current IP: {$t->ip_address}\n";

                /* Delete DNS */
                print "Deleting DNS records...\n";
                TestingServersService::deleteDns($t->dns);

                /* Stop Instance */
                print "Stopping EC2...\n";
                $awsResult = AwsService::shutDownInstance($instanceID);
                if ($awsResult === false) {
                    print "Error stopping instance...\n";
                    die;
                }

                print "Waiting for server to stop...\n";
                sleep(120);

                /* Start Instance */
                print "Starting EC2...\n";
                $awsResult = AwsService::startInstance($instanceID);
                if ($awsResult === false) {
                    print "Error starting instance...\n";
                    die;
                }

                print "Waiting for server to start...\n";
                sleep(120);

                /* Get new IP */
                $instance = AwsService::describeInstance($instanceID);
                $publicIp = "";
                $privateIp = "";
                if (isset($instance['Reservations'][0]['Instances'][0]['PublicIpAddress'])) {
                    $publicIp = $instance['Reservations'][0]['Instances'][0]['PublicIpAddress'];
                    $privateIp = $instance['Reservations'][0]['Instances'][0]['PrivateIpAddress'];
                }

                /* Update security group */
                $securityGroup = $t->security_groupID;
                $oldIp = $t->ip_address;
                $cmd = "aws ec2 revoke-security-group-ingress --group-id {$securityGroup} --protocol tcp --port 443 --cidr {$oldIp}/32";
                system($cmd);
                $cmd = "aws ec2 authorize-security-group-ingress --group-id {$securityGroup} --protocol tcp --port 443 --cidr {$publicIp}/32";
                system($cmd);

                print "Updating IP Address to {$publicIp}\n";
                TestingServers::updateIpAddress($t->id, $publicIp);
                print "Updating private IP Address to {$privateIp}\n";
                TestingServers::updatePrivateIpAddress($t->id, $privateIp);

                /* Create DNS */

                $devLegacyVirtualJobShadowCom = env('dev_legacy_virtualjobshadow_com');
                $devLegacyPlannerVirtualJobShadowCom = env('dev_legacy_planner_virtualjobshadow_com');
                $devLegacyAuthVirtualJobShadowCom = env('dev_legacy_auth_virtualjobshadow_com');
                $devLegacyAdminVirtualJobShadowCom = env('dev_legacy_admin_virtualjobshadow_com');
                $devLegacyVjsJuniorCom = env('dev_legacy_vjsjunior_com');

                print "Creating DNS records...\n";
                AwsService::createRecord($t->dns, 'dev-legacy.virtualjobshadow.com', $publicIp, $devLegacyVirtualJobShadowCom);
                AwsService::createRecord($t->dns, 'dev-legacy.admin.virtualjobshadow.com', $publicIp, $devLegacyAdminVirtualJobShadowCom);
                AwsService::createRecord($t->dns, 'dev-legacy.auth.virtualjobshadow.com', $publicIp, $devLegacyAuthVirtualJobShadowCom);
                AwsService::createRecord($t->dns, 'dev-legacy.planner.virtualjobshadow.com', $publicIp, $devLegacyPlannerVirtualJobShadowCom);
                AwsService::createRecord($t->dns, 'dev-legacy.vjsjunior.com', $publicIp, $devLegacyVjsJuniorCom);

                /* Update status to deployed */
                PendingServerQueue::updateServerStatus($t->queueID, 'deployed');
                TestingServers::updateCurrentStatus($t->id, 'deployed');

                print "Testing Server EC2 Done!\n";
            }
        } elseif ($demo->isNotEmpty()) {
            print "Demo Server:\n";
            foreach ($demo as $d) {
                print "DNS: {$d->dns}\n";
                print "Current IP: {$d->ip_address}\n";

                /* Delete DNS */
                print "Deleting DNS records...\n";
                DnsService::deleteDns('virtualjobshadow.com', $d->dns);
                DnsService::deleteDns('virtualjobshadow.com', $d->dns . ".admin");
                DnsService::deleteDns('virtualjobshadow.com', $d->dns . ".auth");
                DnsService::deleteDns('virtualjobshadow.com', $d->dns . ".planner");
                DnsService::deleteDns('vjsjunior.com', $d->dns);

                /* Stop Instance */
                print "Stopping EC2...\n";
                $awsResult = AwsService::shutDownInstance($instanceID);
                if ($awsResult === false) {
                    print "Error stopping instance...\n";
                    die;
                }

                print "Waiting for server to stop...\n";
                sleep(120);

                /* Start Instance */
                print "Starting EC2...\n";
                $awsResult = AwsService::startInstance($instanceID);
                if ($awsResult === false) {
                    print "Error starting instance...\n";
                    die;
                }

                print "Waiting for server to start...\n";
                sleep(120);

                /* Get new IP */
                $instance = AwsService::describeInstance($instanceID);
                $publicIp = "";
                if (isset($instance['Reservations'][0]['Instances'][0]['PublicIpAddress'])) {
                    $publicIp = $instance['Reservations'][0]['Instances'][0]['PublicIpAddress'];
                }

                /* Update security group */
                $securityGroup = $d->security_groupID;
                $oldIp = $d->ip_address;
                $cmd = "aws ec2 revoke-security-group-ingress --group-id {$securityGroup} --protocol tcp --port 443 --cidr {$oldIp}/32";
                system($cmd);
                $cmd = "aws ec2 authorize-security-group-ingress --group-id {$securityGroup} --protocol tcp --port 443 --cidr {$publicIp}/32";
                system($cmd);

                print "Updating IP Address to {$publicIp}\n";
                DemoServers::updateIpAddress($d->id, $publicIp);

                /* Create DNS */
                print "Creating DNS records...\n";
                DnsService::createDns('A', $d->dns, 'virtualjobshadow.com', $publicIp);
                DnsService::createDns('A', $d->dns . '.admin', 'virtualjobshadow.com', $publicIp);
                DnsService::createDns('A', $d->dns . '.auth', 'virtualjobshadow.com', $publicIp);
                DnsService::createDns('A', $d->dns . '.planner', 'virtualjobshadow.com', $publicIp);
                DnsService::createDns('A', $d->dns, 'vjsjunior.com', $publicIp);

                print "Demo Server EC2 Done!\n";
            }
        } else {
            print "Non Testing/Demo Server:\n";

            /* Stop Instance */
            print "Stopping EC2...\n";
            $awsResult = AwsService::shutDownInstance($instanceID);
            if ($awsResult === false) {
                print "Error stopping instance...\n";
                die;
            }

            print "Waiting for server to stop...\n";
            sleep(120);

            /* Start Instance */
            print "Starting EC2...\n";
            $awsResult = AwsService::startInstance($instanceID);
            if ($awsResult === false) {
                print "Error starting instance...\n";
                die;
            }

            print "Waiting for server to start...\n";
            sleep(120);

            print "Non Testing/Demo Server EC2 Done!\n";
        }
    }
}
