<?php

namespace Pterodactyl\Events\Server;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Pterodactyl\Models\Server;

class Installed
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
