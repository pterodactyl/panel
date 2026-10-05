<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Nodes;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations\BlockDeleteAllocationRequest;
use Pterodactyl\Models\Node;
use Pterodactyl\Support\JsonValueGuard;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Node Allocations', 'Create, update, retrieve, and delete node allocations.')]
class DeleteAllocationBlockController extends AdminApiController
{
    /**
     * Delete unassigned allocations for IP block.
     */
    #[Endpoint('Delete node allocation IP block', 'Deletes every unassigned allocation for an IP address on a node.')]
    #[ScribeResponse(status: 204, description: 'Unassigned allocations for the IP were deleted.')]
    public function __invoke(BlockDeleteAllocationRequest $request, Node $node): Response
    {
        $ip = JsonValueGuard::string($request->validated('ip'));
        $node->allocations()->unassigned()->where('ip', $ip)->delete();

        Activity::event('admin:node-allocation.delete-block')
            ->subject($node)
            ->property('address', $ip)
            ->log();

        return $this->returnNoContent();
    }
}
