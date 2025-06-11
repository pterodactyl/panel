<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;

class Love extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'n:love';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '<3';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Ari <3');
        return Command::SUCCESS;
    }
}
