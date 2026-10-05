<?php

declare(strict_types=1);

namespace Pterodactyl\Events\Server;

use Illuminate\Queue\SerializesModels;
use Pterodactyl\Events\Event;
use Pterodactyl\Models\Server;

class Deleted extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Server $server) {}
}
