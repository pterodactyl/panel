<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Server;

interface ServerRepositoryInterface extends RepositoryInterface
{
    /**
     * Return a server by UUID.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getByUuid(string $uuid): Server;
}
