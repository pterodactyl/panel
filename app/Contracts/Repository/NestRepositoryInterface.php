<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Nest;

interface NestRepositoryInterface extends RepositoryInterface
{
    /**
     * Return a nest or all nests with their associated eggs and variables.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithEggs(int $id = null): Nest;
}
