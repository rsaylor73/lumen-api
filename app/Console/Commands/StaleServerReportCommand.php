<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TestingServers;
use App\Models\StaleServer;
use App\Service\ReportsService;

class StaleServerReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:stale-server-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates stale server email report.';

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
        $data = StaleServer::emailReport();
        if (is_object($data)) {
            foreach ($data as $d) {
                print "Sending report for {$d->id} to {$d->email}\n";
                ReportsService::staleServerReport($d->email, $d->dns);
                StaleServer::setReportSent($d->id);
            }
        }
        print "Done\n";
    }
}
