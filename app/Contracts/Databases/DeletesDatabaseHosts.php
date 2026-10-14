<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Databases;

use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Models\DatabaseHost;

interface DeletesDatabaseHosts
{
    /**
     * A host with databases on it is not deleted.
     *
     * @throws HasActiveServersException
     */
    public function delete(DatabaseHost $host): void;
}
