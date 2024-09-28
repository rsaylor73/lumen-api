<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Service\AwsService;
use App\Service\ReportsService;

class Ec2DegradeMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ec2-degrade-monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will monitor any EC2 for degrade status.';

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
        $data = AwsService::describeInstances();
        $instances = $data['Reservations'];

        if (is_array($instances)) {
            if (!empty($instances)) {
                foreach ($instances as $key => $value) {
                    print "Instance ID: {$instances[$key]['Instances'][0]['InstanceId']} : ";
                    $status = AwsService::describeInstanceStatus($instances[$key]['Instances'][0]['InstanceId']);
                    if (isset($status['InstanceStatuses'][0]['Events'])) {
                        $description = $status['InstanceStatuses'][0]['Events'][0]['Description'];
                        if (preg_match("/Completed/i", $description)) {
                            print "OK! Degraded fixed\n";
                        } else {
                            print "Errors! : {$description}\n";
                            ReportsService::degradedEc2Server($instances[$key]['Instances'][0]['InstanceId']);
                        }
                    } else {
                        print "OK!\n";
                    }
                }
            }
        }
    }
}
