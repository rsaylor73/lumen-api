<?php

namespace App\Console\Commands;

use App\Service\AwsService;
use App\Service\DnsService;
use App\Service\TestingServersService;
use Illuminate\Console\Command;
use App\Models\RefreshDnsQueue;

class RefreshDnsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:refresh-dns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will refresh DNS';

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
        $data = RefreshDnsQueue::getPendingDnsRefreshList('pending');
        if (!empty($data)) {
            foreach ($data as $d) {
                RefreshDnsQueue::updateServerStatus($d->id, 'running');

                print "Deleting DNS {$d->dns}\n";
                TestingServersService::deleteDns($d->dns);

                print "Waiting for DNS to clear...\n";
                sleep(300);

                print "Creating DNS records...\n";

                $devLegacyVirtualJobShadowCom = env('dev_legacy_virtualjobshadow_com');
                $devLegacyPlannerVirtualJobShadowCom = env('dev_legacy_planner_virtualjobshadow_com');
                $devLegacyAuthVirtualJobShadowCom = env('dev_legacy_auth_virtualjobshadow_com');
                $devLegacyAdminVirtualJobShadowCom = env('dev_legacy_admin_virtualjobshadow_com');
                $devLegacyVjsJuniorCom = env('dev_legacy_vjsjunior_com');

                print "Creating DNS records...\n";
                AwsService::createRecord($d->dns, 'dev-legacy.virtualjobshadow.com', $d->ip_address, $devLegacyVirtualJobShadowCom);
                AwsService::createRecord($d->dns, 'dev-legacy.admin.virtualjobshadow.com', $d->ip_address, $devLegacyAdminVirtualJobShadowCom);
                AwsService::createRecord($d->dns, 'dev-legacy.auth.virtualjobshadow.com', $d->ip_address, $devLegacyAuthVirtualJobShadowCom);
                AwsService::createRecord($d->dns, 'dev-legacy.planner.virtualjobshadow.com', $d->ip_address, $devLegacyPlannerVirtualJobShadowCom);
                AwsService::createRecord($d->dns, 'dev-legacy.vjsjunior.com', $d->ip_address, $devLegacyVjsJuniorCom);

                print "\nDone\n";
                RefreshDnsQueue::updateServerStatus($d->id, 'complete');
            }
        }
    }
}
