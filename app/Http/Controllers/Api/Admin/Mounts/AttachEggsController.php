<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Mounts;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Mounts\AttachesEggsToMounts;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Mounts\AttachEggsRequest;
use Pterodactyl\Models\Mount;
use Pterodactyl\Transformers\Api\Admin\MountTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Mounts', 'Create and manage mount definitions and their egg or node attachments.')]
class AttachEggsController extends AdminApiController
{
    /**
     * Attach eggs to mount.
     *
     * @return ApiPayload
     */
    #[Endpoint('Attach eggs to mount', 'Attaches one or more eggs to a mount definition without removing existing egg attachments.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "eggs", "nodes", and "servers".', required: false, example: 'eggs,nodes', enum: ['eggs', 'nodes', 'servers', 'eggs,nodes', 'eggs,nodes,servers'])]
    #[ResponseFromTransformer(MountTransformer::class, Mount::class, description: 'Mount updated.', resourceKey: 'mount')]
    public function __invoke(AttachEggsRequest $request, AttachesEggsToMounts $attacher, Mount $mount): array
    {
        $eggs = $request->eggs();

        $attacher->attach($mount, $eggs);

        Activity::event('admin:mount.attach-egg')
            ->subject($mount)
            ->property('eggs', $eggs)
            ->log();

        return Fractal::item($mount)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->toResponseArray();
    }
}
