<?php

namespace Pterodactyl\Services\Eggs;

use Pterodactyl\Exceptions\Service\Egg\HasChildrenException;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Models\Egg;

class EggDeletionService
{
    /**
     * EggDeletionService constructor.
     */
    public function __construct(protected ServerRepositoryInterface $serverRepository)
    {
    }

    /**
     * Delete an Egg from the database if it has no active servers attached to it.
     *
     * @throws HasActiveServersException
     * @throws HasChildrenException
     */
    public function handle(int $egg): int
    {
        $servers = $this->serverRepository->findCountWhere([['egg_id', '=', $egg]]);
        if ($servers > 0) {
            throw new HasActiveServersException(trans('exceptions.nest.egg.delete_has_servers'));
        }

        $children = Egg::query()->where('config_from', $egg)->count();
        if ($children > 0) {
            throw new HasChildrenException(trans('exceptions.nest.egg.has_children'));
        }

        $egg = Egg::query()->findOrFail($egg);

        return $egg->delete();
    }
}
