<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Databases;

use Pterodactyl\Contracts\Databases\DeletesDatabaseHosts;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Models\DatabaseHost;

final readonly class DeleteDatabaseHost implements DeletesDatabaseHosts
{
    public function delete(DatabaseHost $host): void
    {
        if ($host->databases()->exists()) {
            throw new HasActiveServersException(trans('exceptions.databases.delete_has_databases'));
        }

        $host->delete();
    }
}
