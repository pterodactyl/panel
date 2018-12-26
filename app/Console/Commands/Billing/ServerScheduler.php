<?php

namespace Pterodactyl\Console\Commands\Billing;

use Illuminate\Console\Command;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;

class ServerScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'p:billing:server-scheduler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @var \Pterodactyl\Services\Servers\SuspensionService
     */
    private $suspensionService;

    /**
     * SuspensionController constructor.
     *
     * @param \Pterodactyl\Services\Servers\SuspensionService       $suspensionService
     */
    public function __construct(
        SuspensionService $suspensionService
    ) {
        parent::__construct();

        $this->suspensionService = $suspensionService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        foreach (User::where('balance', '<=', 0)->whereNotNull('stripe_customer_id')->get() as $owner) {
            $total_price = $owner->servers()->sum('monthly_cost');
            $this->info("Making payment for user #{$owner->id} of \${$total_price}...");
            try {
                $charge = Charge::create([
                    'customer' => $owner->stripe_customer_id,
                    'amount'   => $total_price * 100,
                    'currency' => 'usd'
                ]);
                if ($charge->paid) $owner->addBalance($total_price);
            } catch (\Exception $ex) {
                $this->error("Failed to charge user #{$owner->id}: $ex->message");
            }
        }
        foreach(Server::where('monthly_cost', '>', 0)->get() as $server) {
            $owner = $server->user()->first();
            if ($owner->balance > 0 && $server->suspended == 1) {
                $this->info("Resuming Server #{$server->id}");
                try {
                    $this->suspensionService->toggle($server, SuspensionService::ACTION_UNSUSPEND);
                } catch (\Exception $ex) {
                    $this->error("Cannot unsuspend server #{$server->id}: $ex->message");
                }
            } else if ($owner->balance <= 0 && $server->suspended == 0) {
                $this->info("Suspending Server #{$server->id}");
                try {
                    $this->suspensionService->toggle($server, SuspensionService::ACTION_SUSPEND);
                } catch (\Exception $ex) {
                    $this->error("Cannot unsuspend server {$server->id}: $ex->message");
                }
            }
        }
    }
}
