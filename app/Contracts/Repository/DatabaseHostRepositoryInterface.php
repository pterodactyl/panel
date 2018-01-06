<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;
use Pterodactyl\Models\DatabaseHost;

interface DatabaseHostRepositoryInterface extends RepositoryInterface
{
    /**
     * Return database hosts with a count of databases and the node
     * information for which it is attached.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getWithViewDetails(): Collection;

    /**
     * Return a database host with the databases and associated servers
     * that are attached to said databases.
     *
     * @param int $id
     * @return \Pterodactyl\Models\DatabaseHost
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithServers(int $id): DatabaseHost;
}
