<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Nodes;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Allocations\DeletesAllocations;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations\BulkDeleteAllocationRequest;
use Pterodactyl\Models\Node;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Node Allocations', 'Create, update, retrieve, and delete node allocations.')]
class BulkDeleteAllocationsController extends AdminApiController
{
    /**
     * Delete selected unassigned allocations.
     */
    #[Endpoint('Bulk delete node allocations', 'Deletes multiple unassigned allocations from a node by allocation ID.')]
    #[ScribeResponse(status: 204, description: 'Allocations deleted.')]
    #[ScribeResponse(AllocationController::SERVER_USING_ALLOCATION_ERROR, status: 400, description: 'One of the allocations is currently assigned to a server.')]
    public function __invoke(BulkDeleteAllocationRequest $request, DeletesAllocations $deletions, Node $node): Response
    {
        $allocations = $node->allocations()
            ->whereIn('id', $request->validated('ids', []))
            ->get();

        foreach ($allocations as $allocation) {
            $deletions->delete($allocation);

            Activity::event('admin:node-allocation.delete')
                ->subject($allocation)
                ->property('address', $allocation->ip)
                ->property('port', $allocation->port)
                ->log();
        }

        return $this->returnNoContent();
    }
}
