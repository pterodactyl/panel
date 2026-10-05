<?php

declare(strict_types=1);

namespace Pterodactyl\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;
use Pterodactyl\Events\Auth\DirectLogin;
use Pterodactyl\Extensions\Illuminate\Events\Contracts\SubscribesToEvents;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Support\JsonValueGuard;

class AuthenticationListener implements SubscribesToEvents
{
    /**
     * Handles an authentication event by logging the user and information about
     * the request.
     */
    public function login(Failed|DirectLogin $event): void
    {
        $activity = Activity::withRequestMetadata();
        if ($event->user) {
            $activity = $activity->subject($event->user);
        }

        if ($event instanceof Failed) {
            foreach ($event->credentials as $key => $value) {
                JsonValueGuard::assertValue($value);
                $activity = $activity->property($key, $value);
            }
        }

        $activity->event($event instanceof Failed ? 'auth:fail' : 'auth:success')->log();
    }

    public function reset(PasswordReset $event): void
    {
        Activity::event('auth:password-reset')->withRequestMetadata()->subject($event->user)->log();
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Failed::class, self::login(...));
        $events->listen(DirectLogin::class, self::login(...));
        $events->listen(PasswordReset::class, self::reset(...));
    }
}
