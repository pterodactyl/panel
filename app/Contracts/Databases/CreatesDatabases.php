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

    /** @param DatabaseCreationData $data
     * @throws Throwable
     * @throws TooManyDatabasesException
     * @throws DatabaseClientFeatureNotEnabledException
     */
    public function create(Server $server, array $data): Database;
}
