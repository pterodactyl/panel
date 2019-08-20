<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Events\Server;

use App\Models\Server;
use Illuminate\Queue\SerializesModels;

class Created
{
    use SerializesModels;

    /**
     * The Eloquent model of the server.
     *
     * @var \App\Models\Server
     */
    public $server;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }
}
