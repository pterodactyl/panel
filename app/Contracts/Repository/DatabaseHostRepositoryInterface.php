<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;

interface DatabaseHostRepositoryInterface extends RepositoryInterface
{
    /**
     * Return database hosts with a count of databases and the node
     * information for which it is attached.
     */
    public function getWithViewDetails(): Collection;
}
