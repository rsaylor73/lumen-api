<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Service\ReportsService;

class TestingServerErrorReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:testing-server-error-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Returns EC2 instances still running but in error.';

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
        ReportsService::testingServerErrorReport();
    }
}
