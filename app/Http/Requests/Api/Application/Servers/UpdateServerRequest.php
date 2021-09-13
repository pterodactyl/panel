<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateServerRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        $rules = Server::getRules();

        return [
            'external_id' => $rules['external_id'],
            'name' => $rules['name'],
            'description' => array_merge(['nullable'], $rules['description']),
            'owner_id' => $rules['owner_id'],
            'oom_killer' => 'sometimes|boolean',

            'memory' => $rules['memory'],
            'swap' => $rules['swap'],
            'disk' => $rules['disk'],
            'io' => $rules['io'],
            'threads' => $rules['threads'],
            'cpu' => $rules['cpu'],

            'databases' => $rules['database_limit'],
            'allocations' => $rules['allocation_limit'],
            'backups' => $rules['backup_limit'],
        ];
    }

    public function validated(): array
    {
        $data = parent::validated();

        return [
            'external_id' => array_get($data, 'external_id'),
            'name' => array_get($data, 'name'),
            'description' => array_get($data, 'description'),
            'owner_id' => array_get($data, 'owner_id'),
            'oom_disabled' => !array_get($data, 'oom_killer'),

            'memory' => array_get($data, 'memory'),
            'swap' => array_get($data, 'swap'),
            'disk' => array_get($data, 'disk'),
            'io' => array_get($data, 'io'),
            'threads' => array_get($data, 'threads'),
            'cpu' => array_get($data, 'cpu'),

            'database_limit' => array_get($data, 'databases'),
            'allocation_limit' => array_get($data, 'allocations'),
            'backup_limit' => array_get($data, 'backups'),
        ];
    }
}
