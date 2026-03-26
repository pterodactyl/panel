<?php

namespace Pterodactyl\Listeners;

use Pterodactyl\Facades\Activity;
use Illuminate\Contracts\Events\Dispatcher;
use Pterodactyl\Events\Auth\ProvidedAuthenticationToken;
use Pterodactyl\Extensions\Illuminate\Events\Contracts\SubscribesToEvents;

class TwoFactorListener implements SubscribesToEvents
{
    public function __invoke(ProvidedAuthenticationToken $event): void
    {
        Activity::event($event->recovery ? 'auth:recovery-token' : 'auth:token')
            ->withRequestMetadata()
            ->subject($event->user)
            ->log();
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(ProvidedAuthenticationToken::class, self::class);
    }
}
