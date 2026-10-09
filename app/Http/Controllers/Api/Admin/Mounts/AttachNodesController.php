<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Mounts;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Mounts\AttachesNodesToMounts;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Mounts\AttachNodesRequest;
use Pterodactyl\Models\Mount;
use Pterodactyl\Transformers\Api\Admin\MountTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Mounts', 'Create and manage mount definitions and their egg or node attachments.')]
class AttachNodesController extends AdminApiController
{
    /**
     * Attach nodes to mount.
     *
     * @return ApiPayload
     */
    #[Endpoint('Attach nodes to mount', 'Attaches one or more nodes to a mount definition without removing existing node attachments.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "eggs", "nodes", and "servers".', required: false, example: 'eggs,nodes', enum: ['eggs', 'nodes', 'servers', 'eggs,nodes', 'eggs,nodes,servers'])]
    #[ResponseFromTransformer(MountTransformer::class, Mount::class, description: 'Mount updated.', resourceKey: 'mount')]
    public function __invoke(AttachNodesRequest $request, AttachesNodesToMounts $attacher, Mount $mount): array
    {
        $nodes = $request->nodes();

        $attacher->attach($mount, $nodes);

        Activity::event('admin:mount.attach-node')
            ->subject($mount)
            ->property('nodes', $nodes)
            ->log();

        return Fractal::item($mount)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->toResponseArray();
    }
}
