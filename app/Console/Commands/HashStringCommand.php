<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\EncryptionDecryptException;
use Illuminate\Support\Facades\Crypt;

class HashStringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'utility:hash-string {string}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will hash a string provided in the 1st ARGV.';

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
        $string = $this->argument('string');
        $crypt = Crypt::encrypt($string);
        print "$crypt\n";
    }
}
