<?php

namespace Pterodactyl\Http\Requests\Api\Application\Databases;

use Pterodactyl\Models\DatabaseHost;

class UpdateDatabaseRequest extends StoreDatabaseRequest
{
    /**
     * ?
     *
     * @param array|null $rules
     *
     * @return array
     */
    public function rules(array $rules = null): array
    {
        return $rules ?? DatabaseHost::getRulesForUpdate($this->route()->parameter('database')->id);
    }
}
