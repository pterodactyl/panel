<?php

declare(strict_types=1);

namespace Pterodactyl\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Pterodactyl\Events\Auth\ProvidedAuthenticationToken;
use Pterodactyl\Extensions\Illuminate\Events\Contracts\SubscribesToEvents;
use Pterodactyl\Facades\Activity;

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
