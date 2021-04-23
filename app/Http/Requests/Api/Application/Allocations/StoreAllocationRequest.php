<?php

namespace Pterodactyl\Http\Requests\Api\Application\Allocations;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreAllocationRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_ALLOCATIONS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::WRITE;

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
     * @return array
     */
    public function validated()
    {
        $data = parent::validated();

        return [
            'allocation_ip' => $data['ip'],
            'allocation_ports' => $data['ports'],
            'allocation_alias' => $data['alias'] ?? null,
        ];
    }
}
