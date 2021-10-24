<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Models\Server;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreServerRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        $rules = Server::getRules();

        return [
            'external_id' => $rules['external_id'],
            'name' => $rules['name'],
            'description' => array_merge(['nullable'], $rules['description']),
            'owner_id' => $rules['owner_id'],
            'node_id' => $rules['node_id'],

            'limits' => 'required|array',
            'limits.memory' => $rules['memory'],
            'limits.swap' => $rules['swap'],
            'limits.disk' => $rules['disk'],
            'limits.io' => $rules['io'],
            'limits.threads' => $rules['threads'],
            'limits.cpu' => $rules['cpu'],
            'limits.oom_killer' => 'required|boolean',

            'feature_limits' => 'required|array',
            'feature_limits.allocations' => $rules['allocation_limit'],
            'feature_limits.backups' => $rules['backup_limit'],
            'feature_limits.databases' => $rules['database_limit'],

            'allocation.default' => 'required|bail|integer|exists:allocations,id',
            'allocation.additional.*' => 'integer|exists:allocations,id',

            'startup' => $rules['startup'],
            'environment' => 'present|array',
            'egg_id' => $rules['egg_id'],
            'image' => $rules['image'],
            'skip_scripts' => 'present|boolean',
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
            'node_id' => array_get($data, 'node_id'),

            'memory' => array_get($data, 'limits.memory'),
            'swap' => array_get($data, 'limits.swap'),
            'disk' => array_get($data, 'limits.disk'),
            'io' => array_get($data, 'limits.io'),
            'threads' => array_get($data, 'limits.threads'),
            'cpu' => array_get($data, 'limits.cpu'),
            'oom_disabled' => !array_get($data, 'limits.oom_killer'),

            'allocation_limit' => array_get($data, 'feature_limits.allocations'),
            'backup_limit' => array_get($data, 'feature_limits.backups'),
            'database_limit' => array_get($data, 'feature_limits.databases'),

            'allocation_id' => array_get($data, 'allocation.default'),
            'allocation_additional' => array_get($data, 'allocation.additional'),

            'startup' => array_get($data, 'startup'),
            'environment' => array_get($data, 'environment'),
            'egg_id' => array_get($data, 'egg'),
            'image' => array_get($data, 'image'),
            'skip_scripts' => array_get($data, 'skip_scripts'),
            'start_on_completion' => array_get($data, 'start_on_completion', false),
        ];
    }
}
