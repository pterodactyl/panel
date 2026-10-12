<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Mounts;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Mounts\AttachesEggsToMounts;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Mounts\AttachEggsRequest;
use Pterodactyl\Http\Resources\Api\Admin\MountResource;
use Pterodactyl\Models\Mount;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Mounts', 'Create and manage mount definitions and their egg or node attachments.')]
class AttachEggsController extends AdminApiController
{
    /**
     * Attach eggs to mount.
     */
    #[Endpoint('Attach eggs to mount', 'Attaches one or more eggs to a mount definition without removing existing egg attachments.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "eggs", "nodes", and "servers".', required: false, example: 'eggs,nodes', enum: ['eggs', 'nodes', 'servers', 'eggs,nodes', 'eggs,nodes,servers'])]
    #[ResponseFromResource(MountResource::class, Mount::class, description: 'Mount updated.', resourceKey: 'mount')]
    public function __invoke(AttachEggsRequest $request, AttachesEggsToMounts $attacher, Mount $mount): MountResource
    {
        $eggs = $request->eggs();

        $attacher->attach($mount, $eggs);

        Activity::event('admin:mount.attach-egg')
            ->subject($mount)
            ->property('eggs', $eggs)
            ->log();

        return MountResource::make($mount);
    }
}
