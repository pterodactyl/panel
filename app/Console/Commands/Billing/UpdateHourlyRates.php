<?php

namespace Pterodactyl\Console\Commands\Billing;

use Illuminate\Console\Command;
use Pterodactyl\Models\Server;

class UpdateHourlyRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'p:billing:update-hourly-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge users for each hour of server usage';

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
        $servers = Server::where('montly_price', '>', 0)->get();
        foreach ($servers as $server) {
            $hourly_price = $server->montly_price / 720;
            $user = $server->user;
            $server->this_month_cost += $hourly_price;
            $server->save();
            $user->monthly_cost += $hourly_price;
            $user->save();
        }
    }
}
