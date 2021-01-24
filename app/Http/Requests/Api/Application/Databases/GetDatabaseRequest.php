<?php

namespace Pterodactyl\Http\Requests\Api\Application\Databases;

use Pterodactyl\Models\DatabaseHost;

class GetDatabaseRequest extends GetDatabasesRequest
{
    public function resourceExists(): bool
    {
        $databaseHost = $this->route()->parameter('databaseHost');

        return $databaseHost instanceof DatabaseHost && $databaseHost->exists;
    }
}
