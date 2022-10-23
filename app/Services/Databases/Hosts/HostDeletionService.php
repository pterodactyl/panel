<?php

namespace Pterodactyl\Services\Databases\Hosts;

use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;

class HostDeletionService
{
    /**
     * HostDeletionService constructor.
     */
    public function __construct(
        private DatabaseRepositoryInterface $databaseRepository
    ) {
    }

    /**
     * Delete a specified host from the Panel if no databases are
     * attached to it.
     *
     * @throws HasActiveServersException
     */
    public function handle(int $host): int
    {
        $count = $this->databaseRepository->findCountWhere([['database_host_id', '=', $host]]);
        if ($count > 0) {
            throw new HasActiveServersException(trans('exceptions.databases.delete_has_databases'));
        }

        $host = DatabaseHost::query()->find($host);
        if ($host) {
            return $host->delete();
        }

        return true;
    }
}
