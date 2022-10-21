<?php

namespace Pterodactyl\Services\Nests;

use Pterodactyl\Models\Server;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Exceptions\Service\HasActiveServersException;

class NestDeletionService
{
    /**
     * NestDeletionService constructor.
     */
    public function __construct(
        protected NestRepositoryInterface $repository
    ) {
    }

    /**
     * Delete a nest from the system only if there are no servers attached to it.
     *
     * @throws HasActiveServersException
     */
    public function handle(int $nest): int
    {
        if (Server::query()->where('nest_id', $nest)->count() > 0) {
            throw new HasActiveServersException(trans('exceptions.nest.delete_has_servers'));
        }

        return $this->repository->delete($nest);
    }
}
