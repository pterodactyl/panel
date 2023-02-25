<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Notifications\ServersSuspendedNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChargeServers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'servers:charge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge all users with severs that are due to be charged';

        /**
     * A list of users that have to be notified
     * @var array
     */
    protected $usersToNotify = [];

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
        Server::whereNull('suspended')->with('user', 'product')->chunk(10, function ($servers) {
            /** @var Server $server */
            foreach ($servers as $server) {
                /** @var Product $product */
                $product = $server->product;
                /** @var User $user */
                $user = $server->user;

                $billing_period = $product->billing_period;


                // check if server is due to be charged by comparing its last_billed date with the current date and the billing period
                $newBillingDate = null;
                switch($billing_period) {
                    case 'annually':
                        $newBillingDate = Carbon::parse($server->last_billed)->addYear();
                        break;
                    case 'half-annually':
                        $newBillingDate = Carbon::parse($server->last_billed)->addMonths(6);
                        break;
                    case 'quarterly':
                        $newBillingDate = Carbon::parse($server->last_billed)->addMonths(3);
                        break;
                    case 'monthly':
                        $newBillingDate = Carbon::parse($server->last_billed)->addMonth();
                        break;
                    case 'weekly':
                        $newBillingDate = Carbon::parse($server->last_billed)->addWeek();
                        break;
                    case 'daily':
                        $newBillingDate = Carbon::parse($server->last_billed)->addDay();
                        break;
                    case 'hourly':
                        $newBillingDate = Carbon::parse($server->last_billed)->addHour();
                    default:
                        $newBillingDate = Carbon::parse($server->last_billed)->addHour();
                        break;
                };

                if (!($newBillingDate->isPast())) {
                    continue;
                }

                // check if the server is canceled or if user has enough credits to charge the server or
                if ( $server->cancelled || $user->credits <= $product->price) {
                    try {
                        // suspend server
                        $this->line("<fg=yellow>{$server->name}</> from user: <fg=blue>{$user->name}</> has been <fg=red>suspended!</>");
                        $server->suspend();

                        // add user to notify list
                        if (!in_array($user, $this->usersToNotify)) {
                            array_push($this->usersToNotify, $user);
                        }
                    } catch (\Exception $exception) {
                        $this->error($exception->getMessage());
                    }
                    return;
                }

                // charge credits to user
                $this->line("<fg=blue>{$user->name}</> Current credits: <fg=green>{$user->credits}</> Credits to be removed: <fg=red>{$product->price}</>");
                $user->decrement('credits', $product->price);

                // update server last_billed date in db
                DB::table('servers')->where('id', $server->id)->update(['last_billed' => $newBillingDate]);
            }

            return $this->notifyUsers();
        });
    }

    /**
     * @return bool
     */
    public function notifyUsers()
    {
        if (!empty($this->usersToNotify)) {
            /** @var User $user */
            foreach ($this->usersToNotify as $user) {
                $this->line("<fg=yellow>Notified user:</> <fg=blue>{$user->name}</>");
                $user->notify(new ServersSuspendedNotification());
            }
        }

        #reset array
        $this->usersToNotify = array();
        return true;
    }
}
