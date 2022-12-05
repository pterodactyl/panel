<?php

namespace Pterodactyl\Http\Requests\Api\Application\Databases;

use Pterodactyl\Models\DatabaseHost;

class UpdateDatabaseRequest extends StoreDatabaseRequest
{
    public function rules(array $rules = null): array
    {
        /** @var DatabaseHost $databaseHost */
        $databaseHost = $this->route()->parameter('databaseHost');

        return $rules ?? DatabaseHost::getRulesForUpdate($databaseHost->id);
    }
}
