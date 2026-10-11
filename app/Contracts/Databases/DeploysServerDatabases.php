<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Databases;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException;
use Pterodactyl\Exceptions\Service\Database\NoSuitableDatabaseHostException;
use Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;
use Throwable;

interface DeploysServerDatabases
{
    /**
     * Create a database for a server within its database limit, prefixing the name with
     * "s{server_id}_". With no database_host_id, a host on the server's node is picked, or
     * any host when random hosts are allowed. The input is validated here, so it can come
     * straight from any caller.
     *
     * @param  ActionInput  $input  database and remote, and optionally database_host_id and max_connections
     *
     * @throws Throwable
     * @throws TooManyDatabasesException
     * @throws DatabaseClientFeatureNotEnabledException
     * @throws NoSuitableDatabaseHostException
     * @throws DisplayException
     */
    public function deploy(Server $server, array $input): Database;
}
