<?php

namespace Pterodactyl\Events\Server;

use Pterodactyl\Events\Event;
use Pterodactyl\Models\Server;
use Illuminate\Queue\SerializesModels;

class Installed extends Event
{
    use SerializesModels;

    /**
     * @var \Pterodactyl\Models\Server
     */
    public $server;

    /**
     * Create a new event instance.
     *
     * @var \Pterodactyl\Models\Server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }
}
