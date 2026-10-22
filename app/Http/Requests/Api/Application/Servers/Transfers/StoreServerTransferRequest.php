<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers\Transfers;

use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class StoreServerTransferRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_SERVERS;

    protected int $permission = AdminAcl::WRITE;

    public function rules(): array
    {
        return [
            'node_id' => 'required|exists:nodes,id',
            'allocation_id' => 'nullable|bail|unique:servers|exists:allocations,id',
            'allocation_additional' => 'nullable',
        ];
    }
}
