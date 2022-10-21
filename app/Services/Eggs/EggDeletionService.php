<?php

namespace Pterodactyl\Services\Eggs;

use Pterodactyl\Models\Server;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Exceptions\Service\Egg\HasChildrenException;
use Pterodactyl\Exceptions\Service\HasActiveServersException;

class EggDeletionService
{
    /**
     * EggDeletionService constructor.
     */
    public function __construct(
        protected EggRepositoryInterface $repository
    ) {
    }

    /**
     * Delete an Egg from the database if it has no active servers attached to it.
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     * @throws \Pterodactyl\Exceptions\Service\Egg\HasChildrenException
     */
    public function handle(int $egg): int
    {
        if (Server::query()->where('egg_id', $egg)->count()) {
            throw new HasActiveServersException(trans('exceptions.nest.egg.delete_has_servers'));
        }

        $children = $this->repository->findCountWhere([['config_from', '=', $egg]]);
        if ($children > 0) {
            throw new HasChildrenException(trans('exceptions.nest.egg.has_children'));
        }

        return $this->repository->delete($egg);
    }
}
