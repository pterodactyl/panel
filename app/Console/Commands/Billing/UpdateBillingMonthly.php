<?php

namespace Pterodactyl\Console\Commands\Billing;

use Illuminate\Console\Command;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;

class UpdateBillingMonthly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'p:billing:update-billing-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return mixed
     */
    public function handle()
    {
        $this->info("Updating users wallets...");
        $users = User::where('monthly_cost', '>', 0)->get();
        $servers = Server::where('monthly_cost', '>', 0)->get();
        foreach ($users as $user) {
            $user->balance -= $user->monthly_cost;
            $user->monthly_cost = 0;
            $user->save();
        }
        foreach ($servers as $server) {
            $server->this_month_cost = 0;
            $server->save();
        }
    }
}
