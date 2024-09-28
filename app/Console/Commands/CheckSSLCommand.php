<?php

namespace App\Console\Commands;

use App\Models\EventLog;
use App\Models\TestingServers;
use Illuminate\Console\Command;
use App\Models\DemoServers;
use App\Service\BackendCommandService;

class CheckSSLCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:check-ssl {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if SSL is valid. Params: testing | demos';

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
        $type = $this->argument('type');
        $now = date("U");

        switch ($type) {
            case "demos":
                $servers = DemoServers::getServers(
                    'deployed',
                    '1',
                    '99999',
                    '',
                    'id',
                    'DESC'
                );
                break;
            default:
                $servers = TestingServers::getServers(
                    'deployed',
                    '1',
                    '99999',
                    '',
                    '',
                    'id',
                    'DESC',
                    null,
                    null,
                    null
                );
                break;
        }

        $data = $servers['data'];
        foreach ($data as $d) {
            $fixSSL = false;
            $cmd = "curl https://{$d->dns}.virtualjobshadow.com -vI --stderr - | grep \"expire date\"";
            $output = system($cmd);
            $output = explode(PHP_EOL, $output);
            $output = $output[0];
            $output = str_replace('*  expire date: ', '', $output);
            $expire = date("U", strtotime($output));

            $cmd = "curl https://{$d->dns}.virtualjobshadow.com -vI --stderr - | grep \"curl failed to verify the legitimacy of the server\"";
            $output = system($cmd);
            $output = explode(PHP_EOL, $output);
            $output = $output[0];

            if ($expire == 0) {
                $fixSSL = true;
            }

            $timeLeft = ($expire - $now) / 86400;
            if ($timeLeft < 10) {
                $fixSSL = true;
            }

            if (preg_match('/curl failed to verify/i', $output)) {
                $fixSSL = true;
            }

            if ($fixSSL === true) {
                $run = "php artisan command:server-force-ssl {$d->id} {$type}";
                system($run);
            }
        }
    }
}
