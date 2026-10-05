<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Databases;

use Pterodactyl\Models\DatabaseHost;
use Throwable;

interface CreatesDatabaseHosts
{
    /** @param ModelAttributes $data
     * @throws Throwable
     */
    public function create(array $data): DatabaseHost;
}
