<?php

namespace App\Listeners;

class Verified
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        if (! $event->user->email_verified_reward) {
            $event->user->increment('server_limit', config('SETTINGS::USER:SERVER_LIMIT_REWARD_AFTER_VERIFY_EMAIL'));
            $event->user->increment('credits', config('SETTINGS::USER:CREDITS_REWARD_AFTER_VERIFY_EMAIL'));
        }
    }
}
