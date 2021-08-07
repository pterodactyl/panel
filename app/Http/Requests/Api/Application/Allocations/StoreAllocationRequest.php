<?php

namespace Pterodactyl\Http\Requests\Api\Application\Allocations;

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

    public function validated(): array
    {
        $data = parent::validated();

        return [
            'allocation_ip' => $data['ip'],
            'allocation_ports' => $data['ports'],
            'allocation_alias' => $data['alias'] ?? null,
        ];
    }
}
