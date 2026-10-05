<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Data\ServerState;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Models\Server;

interface ReadsServerState
{
    /**
     * Return the live state and resource usage of a server. Readings are shared
     * with the client resources endpoint and cached for up to 20 seconds, so
     * repeated callers do not flood Wings.
     *
     * @throws DaemonConnectionException
     */
    public function read(Server $server): ServerState;
}
