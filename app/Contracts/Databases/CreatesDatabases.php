<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Databases;

use Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException;
use Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;
use Throwable;

interface CreatesDatabases
{
    public function setValidateDatabaseLimit(bool $validate): self;

    /**
     * Create a database for a server on the given database host. The input is validated here,
     * so it can come straight from any caller. The name must already carry the server's
     * "s{server_id}_" prefix (see DatabaseName::generateUnique()); a database given no
     * max_connections takes the column default.
     *
     * @param  array<string, mixed>  $input  database, remote and database_host_id, and optionally max_connections
     *
     * @throws Throwable
     * @throws TooManyDatabasesException
     * @throws DatabaseClientFeatureNotEnabledException
     */
    public function create(Server $server, array $input): Database;
}
