<?php

namespace Pterodactyl\Events\Server;

use Pterodactyl\Events\Event;
use Pterodactyl\Models\Server;
use Illuminate\Queue\SerializesModels;

class Saving extends Event
{
    use SerializesModels;

    /**
     * The Eloquent model of the server.
     */
    public Server $server;

    /**
     * Create a new event instance.
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }
}
