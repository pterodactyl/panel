<?php

namespace App\Http\Requests\Api\Application\Allocations;

use App\Models\Node;
use App\Models\Allocation;
use App\Services\Acl\Api\AdminAcl;
use App\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteAllocationRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_ALLOCATIONS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::WRITE;

    /**
     * Determine if the requested allocation exists and belongs to the node that
     * is being passed in the URL.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        $node = $this->route()->parameter('node');
        $allocation = $this->route()->parameter('allocation');

        if ($node instanceof Node && $node->exists) {
            if ($allocation instanceof Allocation && $allocation->exists && $allocation->node_id === $node->id) {
                return true;
            }
        }

        return false;
    }
}
