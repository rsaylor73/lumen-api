<?php

namespace App\Console\Commands;

use App\Models\EventLog;
use Illuminate\Console\Command;
use App\Models\DemoServers;
use App\Models\TestingServers;
use App\Service\BackendCommandService;

class ServerForceSSLCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:server-force-ssl {id} {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manual command to install SSL. Params: id | testing or demos';

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
        $id = $this->argument('id');
        $type = $this->argument('type');
        switch ($type) {
            case "demos":
                $server = DemoServers::getServerDetails($id);
                break;
            default:
                $server = TestingServers::getServerDetails($id);
                break;
        }
        $cmd = BackendCommandService::generateSSLRenewCommand($server);
        system($cmd);
    }
}
