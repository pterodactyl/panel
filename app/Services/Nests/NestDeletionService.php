<?php

namespace Pterodactyl\Services\Nests;

use Pterodactyl\Models\Nest;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class NestDeletionService
{
    /**
     * NestDeletionService constructor.
     */
    public function __construct(protected ServerRepositoryInterface $serverRepository)
    {
    }

    /**
     * Delete a nest from the system only if there are no servers attached to it.
     *
     * @throws HasActiveServersException
     */
    public function handle(int $nest): int
    {
        $count = $this->serverRepository->findCountWhere([['nest_id', '=', $nest]]);
        if ($count > 0) {
            throw new HasActiveServersException(trans('exceptions.nest.delete_has_servers'));
        }

        $nest = Nest::query()->findOrFail($nest);

        return $nest->delete();
    }
}
