<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Events\Server;

use Pterodactyl\Models\Server;
use Illuminate\Queue\SerializesModels;

class Deleting
{
    use SerializesModels;

    /**
     * The Eloquent model of the server.
     *
     * @var \Pterodactyl\Models\Server
     */
    public $server;

    /**
     * Create a new event instance.
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }
}
