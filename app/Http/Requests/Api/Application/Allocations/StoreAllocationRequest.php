<?php

namespace Pterodactyl\Http\Requests\Api\Application\Allocations;

use Illuminate\Support\Arr;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreAllocationRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'ip' => 'required|string',
            'alias' => 'sometimes|nullable|string|max:191',
            'ports' => 'required|array',
            'ports.*' => 'string',
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
            'allocation_ip' => $data['ip'],
            'allocation_ports' => $data['ports'],
            'allocation_alias' => $data['alias'] ?? null,
        ];

        return is_null($key) ? $response : Arr::get($response, $key, $default);
    }
}
