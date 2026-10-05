<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Databases;

use Pterodactyl\Models\DatabaseHost;
use Throwable;

interface UpdatesDatabaseHosts
{
    /** @param DatabaseHostUpdateData $data
     * @throws Throwable
     */
    public function update(DatabaseHost $host, array $data): DatabaseHost;
}
