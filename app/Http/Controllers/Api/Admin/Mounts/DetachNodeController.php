<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Mounts;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Mounts\DetachNodeRequest;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Mounts', 'Create and manage mount definitions and their egg or node attachments.')]
class DetachNodeController extends AdminApiController
{
    /**
     * Detach node from mount.
     */
    #[Endpoint('Detach node from mount', 'Removes a single node attachment from a mount definition.')]
    #[ScribeResponse(status: 204, description: 'Node detached from mount.')]
    public function __invoke(DetachNodeRequest $request, Mount $mount, Node $node): Response
    {
        $mount->nodes()->detach($node->id);

        Activity::event('admin:mount.detach-node')
            ->subject($mount)
            ->property('node', $node->id)
            ->log();

        return $this->returnNoContent();
    }
}
