<?php

namespace Pterodactyl\Services\Databases\Hosts;

use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Models\DatabaseHost;

class HostDeletionService
{
    /**
     * Delete a specified host from the Panel if no databases are
     * attached to it.
     *
     * @throws HasActiveServersException
     */
    public function handle(int $host): int
    {
        /** @var DatabaseHost $host */
        $host = DatabaseHost::query()->findOrFail($host);

        if ($host->databases()->count() > 0) {
            throw new HasActiveServersException(trans('exceptions.databases.delete_has_databases'));
        }

        return $host->delete();
    }
}
