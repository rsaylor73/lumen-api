<?php

namespace App\Console\Commands;

use App\Service\DnsService;
use Illuminate\Console\Command;
use App\Models\DemoServers;
use App\Service\TestingServersService;
use App\Models\cNameRecordsDemoServers;

class DeleteDemoServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:delete-demo-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will delete a demo server on or past its expiration date.';

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
        $data = DemoServers::getServersReadyToDelete();
        foreach ($data as $d) {
            // Terminate Instance
            print "Terminating server please wait.\n";
            $cmd = "aws ec2 terminate-instances --instance-ids {$d->instanceID}";
            //print "CMD: $cmd\n\n";
            system($cmd);

            // wait 2 mins for server too shutdown
            print "Waiting for server too terminate...\n";
            sleep(120);

            // Terminate Security Group
            print "Deleting security group.\n";
            $cmd = "aws ec2 delete-security-group --group-id {$d->security_groupID}";
            system($cmd);

            // Terminate DNS - Historic this is for Rackspace
            //DnsService::deleteDns('virtualjobshadow.com', $d->dns . ".dev-demo");
            //DnsService::deleteDns('virtualjobshadow.com', $d->dns . ".dev-demo.admin");
            //DnsService::deleteDns('virtualjobshadow.com', $d->dns . ".dev-demo.auth");
            //DnsService::deleteDns('virtualjobshadow.com', $d->dns . ".dev-demo.planner");
            //DnsService::deleteDns('vjsjunior.com', $d->dns . ".dev-demo");

            $demoLegacyVirtualJobShadowCom = env('demo_legacy_virtualjobshadow_com');
            $demoLegacyPlannerVirtualJobShadowCom = env('demo_legacy_planner_virtualjobshadow_com');
            $demoLegacyAuthVirtualJobShadowCom = env('demo_legacy_auth_virtualjobshadow_com');
            $demoLegacyAdminVirtualJobShadowCom = env('demo_legacy_admin_virtualjobshadow_com');
            $demoLegacyVjsJuniorCom = env('demo_legacy_vjsjunior_com');

            TestingServersService::deleteRoute53($d->dns . ".demo-legacy" , "virtualjobshadow.com", $demoLegacyVirtualJobShadowCom);
            TestingServersService::deleteRoute53($d->dns . ".demo-legacy.admin", "virtualjobshadow.com", $demoLegacyAdminVirtualJobShadowCom);
            TestingServersService::deleteRoute53($d->dns . ".demo-legacy.auth", "virtualjobshadow.com", $demoLegacyAuthVirtualJobShadowCom);
            TestingServersService::deleteRoute53($d->dns . ".demo-legacy.planner", "virtualjobshadow.com", $demoLegacyPlannerVirtualJobShadowCom);
            TestingServersService::deleteRoute53($d->dns . ".demo-legacy", "vjsjunior.com", $demoLegacyVjsJuniorCom);


            // Update DB
            DemoServers::updateCurrentStatus($d->id, 'terminated');
            print "Deleted {$d->id}\n\n";
        }
        print "Done!\n";
    }
}
