<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Server;
use App\Models\User;
use App\Notifications\ServersSuspendedNotification;
use Illuminate\Console\Command;

class ChargeCreditsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credits:charge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge all users with active servers';

    /**
     * A list of users that have to be notified
     *
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
     * @return string
     */
    public function handle()
    {
        Server::whereNull('suspended')->chunk(10, function ($servers) {
            /** @var Server $server */
            foreach ($servers as $server) {
                /** @var Product $product */
                $product = $server->product;
                /** @var User $user */
                $user = $server->user;

                //charge credits / suspend server
                if ($user->credits >= $product->getHourlyPrice()) {
                    $this->line("<fg=blue>{$user->name}</> Current credits: <fg=green>{$user->credits}</> Credits to be removed: <fg=red>{$product->getHourlyPrice()}</>");
                    $user->decrement('credits', $product->getHourlyPrice());
                } else {
                    try {
                        //suspend server
                        $this->line("<fg=yellow>{$server->name}</> from user: <fg=blue>{$user->name}</> has been <fg=red>suspended!</>");
                        $server->suspend();

                        //add user to notify list
                        if (!in_array($user, $this->usersToNotify)) {
                            array_push($this->usersToNotify, $user);
                        }
                    } catch (\Exception $exception) {
                        $this->error($exception->getMessage());
                    }
                }
            }
        });

        return $this->notifyUsers();
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

        //reset array
        $this->usersToNotify = [];

        return true;
    }
}
