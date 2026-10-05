<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Illuminate\Events\Contracts;

use Illuminate\Contracts\Events\Dispatcher;

interface SubscribesToEvents
{
    public function subscribe(Dispatcher $events): void;
}
