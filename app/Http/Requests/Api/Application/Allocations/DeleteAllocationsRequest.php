<?php

namespace Pterodactyl\Http\Requests\Api\Application\Allocations;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteAllocationsRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_ALLOCATIONS;

    protected int $permission = AdminAcl::WRITE;

    public function rules(): array
    {
        return [
            'ids' => 'array|required',
        ];
    }

    /**
     * @return array
     */
    public function validated()
    {
        $data = parent::validated();

        return $data;
    }
}
