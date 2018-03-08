<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Models\Server;

class UpdateServerBuildConfigurationRequest extends ServerWriteRequest
{
    /**
     * Return the rules to validate this request aganist.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = Server::getUpdateRulesForId($this->getModel(Server::class)->id);

        return [
            'allocation' => $rules['allocation_id'],
            'memory' => $rules['memory'],
            'swap' => $rules['swap'],
            'io' => $rules['io'],
            'cpu' => $rules['cpu'],
            'disk' => $rules['disk'],
            'add_allocations' => 'bail|array',
            'add_allocations.*' => 'integer',
            'remove_allocations' => 'bail|array',
            'remove_allocations.*' => 'integer',
            'feature_limits' => 'required|array',
            'feature_limits.databases' => $rules['database_limit'],
            'feature_limits.allocations' => $rules['allocation_limit'],
        ];
    }

    /**
     * Convert the allocation field into the expected format for the service handler.
     *
     * @return array
     */
    public function validated()
    {
        $data = parent::validated();

        $data['allocation_id'] = $data['allocation'];
        $data['database_limit'] = $data['feature_limits']['databases'];
        $data['allocation_limit'] = $data['feature_limits']['allocations'];
        unset($data['allocation'], $data['feature_limits']);

        return $data;
    }

    /**
     * Custom attributes to use in error message responses.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'add_allocations' => 'allocations to add',
            'remove_allocations' => 'allocations to remove',
            'add_allocations.*' => 'allocation to add',
            'remove_allocations.*' => 'allocation to remove',
            'feature_limits.databases' => 'Database Limit',
            'feature_limits.allocations' => 'Allocation Limit',
        ];
    }
}
