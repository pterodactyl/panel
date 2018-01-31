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
        $rules = Server::getUpdateRulesForId($this->route()->parameter('server')->id);

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
        unset($data['allocation']);

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
        ];
    }
}
