<?php

namespace App\Events\Server;

use App\Events\Event;
use App\Models\Server;
use Illuminate\Queue\SerializesModels;

class Installed extends Event
{
    use SerializesModels;

    /**
     * @var \App\Models\Server
     */
    public $server;

    /**
     * Create a new event instance.
     *
     * @var \App\Models\Server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }
}
