<?php

namespace App\Console;

use App\Console\Commands\DeleteDemoServerCommand;
use App\Console\Commands\PendingDemoServerCommand;
use App\Console\Commands\SleepTestingServerTerminateCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\PendingServerCommand::class,
        \App\Console\Commands\PendingDemoServerCommand::class,
        \App\Console\Commands\DeleteDemoServerCommand::class,
        \App\Console\Commands\DelayPendingQueueCommand::class,
        \App\Console\Commands\HashStringCommand::class,
        \App\Console\Commands\StaleServerDataCommand::class,
        \App\Console\Commands\StaleServerReportCommand::class,
        \App\Console\Commands\SslRenewalDataCommand::class,
        \App\Console\Commands\RenewSSLQueueCommand::class,
        \App\Console\Commands\ServerForceSSLCommand::class,
        \App\Console\Commands\RestoreSnapshotCommand::class,
        \App\Console\Commands\Ec2DegradeMonitorCommand::class,
        \App\Console\Commands\Ec2DegradeRestoreCommand::class,
        \App\Console\Commands\DeleteTestingServerCommand::class,
        \App\Console\Commands\Ec2HardRebootQueueCommand::class,
        \App\Console\Commands\SleepTestingServersCommand::class,
        \App\Console\Commands\SleepTestingServerTerminateCommand::class,
        \App\Console\Commands\DeleteStuckServers::class,
        \App\Console\Commands\TestingServerErrorReportCommand::class,
        \App\Console\Commands\RefreshDnsCommand::class,
        \App\Console\Commands\CheckSSLCommand::class,
        \App\Console\Commands\snapShotCommand::class,
        \App\Console\Commands\snapShotDailyBackupQueueCommand::class,
        /* V2 */
        \App\Console\Commands\v2\PendingTestingServerCommand::class,
        \App\Console\Commands\v2\PendingDemoServerCommand::class,
        \App\Console\Commands\v2\DeleteServerCommand::class,
        \App\Console\Commands\v2\DeleteDemoServerCommand::class,
        \App\Console\Commands\v2\AutoDeleteDemoServerCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
