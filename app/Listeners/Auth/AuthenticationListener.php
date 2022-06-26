<?php

namespace Pterodactyl\Listeners\Auth;

use Illuminate\Auth\Events\Login;
use Pterodactyl\Facades\Activity;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Events\Dispatcher;
use Pterodactyl\Extensions\Illuminate\Events\Contracts\SubscribesToEvents;

class AuthenticationListener implements SubscribesToEvents
{
    /**
     * Handles an authentication event by logging the user and information about
     * the request.
     *
     * @param \Illuminate\Auth\Events\Login|\Illuminate\Auth\Events\Failed $event
     */
    public function handle($event): void
    {
        $activity = Activity::withRequestMetadata();
        if ($event->user) {
            $activity = $activity->subject($event->user);
        }

        if ($event instanceof Failed) {
            foreach ($event->credentials as $key => $value) {
                $activity = $activity->property($key, $value);
            }
        }

        $activity->event($event instanceof Failed ? 'auth:fail' : 'auth:success')->log();
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Failed::class, self::class);
        $events->listen(Login::class, self::class);
    }
}
