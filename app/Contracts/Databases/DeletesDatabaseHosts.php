<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Databases;

use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Models\DatabaseHost;

interface DeletesDatabaseHosts
{
    /** @throws HasActiveServersException */
    public function delete(DatabaseHost $host): void;
}
