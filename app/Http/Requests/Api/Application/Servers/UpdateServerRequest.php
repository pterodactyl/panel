<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Illuminate\Support\Arr;
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

            'limits' => 'sometimes|array',
            'limits.memory' => $rules['memory'],
            'limits.swap' => $rules['swap'],
            'limits.disk' => $rules['disk'],
            'limits.io' => $rules['io'],
            'limits.threads' => $rules['threads'],
            'limits.cpu' => $rules['cpu'],
            'limits.oom_killer' => 'sometimes|boolean',

            'feature_limits' => 'required|array',
            'feature_limits.allocations' => $rules['allocation_limit'],
            'feature_limits.backups' => $rules['backup_limit'],
            'feature_limits.databases' => $rules['database_limit'],

            'allocation_id' => 'bail|exists:allocations,id',
            'add_allocations' => 'bail|array',
            'add_allocations.*' => 'integer',
            'remove_allocations' => 'bail|array',
            'remove_allocations.*' => 'integer',
        ];
    }

    /**
     * @param string|null $key
     * @param string|array|null $default
     *
     * @return mixed
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated();
        $response = [
            'external_id' => array_get($data, 'external_id'),
            'name' => array_get($data, 'name'),
            'description' => array_get($data, 'description'),
            'owner_id' => array_get($data, 'owner_id'),

            'memory' => array_get($data, 'limits.memory'),
            'swap' => array_get($data, 'limits.swap'),
            'disk' => array_get($data, 'limits.disk'),
            'io' => array_get($data, 'limits.io'),
            'threads' => array_get($data, 'limits.threads'),
            'cpu' => array_get($data, 'limits.cpu'),
            'oom_killer' => array_get($data, 'limits.oom_killer'),

            'allocation_limit' => array_get($data, 'feature_limits.allocations'),
            'backup_limit' => array_get($data, 'feature_limits.backups'),
            'database_limit' => array_get($data, 'feature_limits.databases'),

            'allocation_id' => array_get($data, 'allocation_id'),
            'add_allocations' => array_get($data, 'add_allocations'),
            'remove_allocations' => array_get($data, 'remove_allocations'),
        ];

        return is_null($key) ? $response : Arr::get($response, $key, $default);
    }
}
