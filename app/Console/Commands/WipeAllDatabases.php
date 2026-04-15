<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WipeAllDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:wipe-all-databases';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connections = [
            'commerce',
            'communications',
            'finance',
            'inventory',
            'logistics',
            'notifications',
            'pharma_marketing',
            'platform',
            'reporting',
            'workflow'
        ];

        foreach ($connections as $connection) {
            $this->call('db:wipe', ['--database' => $connection]);
        }

        $this->info('All schemas wiped!');
    }
}
