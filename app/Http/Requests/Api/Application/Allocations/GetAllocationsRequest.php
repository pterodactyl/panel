<?php

namespace Pterodactyl\Http\Requests\Api\Application\Allocations;

use Pterodactyl\Models\Node;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetAllocationsRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_ALLOCATIONS;
    protected int $permission = AdminAcl::READ;

    /**
     * Determine if the node that we are requesting the allocations
     * for exists on the Panel.
     */
    public function resourceExists(): bool
    {
        $node = $this->route()->parameter('node');

        return $node instanceof Node && $node->exists;
    }
}
