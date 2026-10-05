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
use Pterodactyl\Http\Requests\Api\Admin\Mounts\DetachEggRequest;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Mount;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Mounts', 'Create and manage mount definitions and their egg or node attachments.')]
class DetachEggController extends AdminApiController
{
    /**
     * Detach egg from mount.
     */
    #[Endpoint('Detach egg from mount', 'Removes a single egg attachment from a mount definition.')]
    #[ScribeResponse(status: 204, description: 'Egg detached from mount.')]
    public function __invoke(DetachEggRequest $request, Mount $mount, Egg $egg): Response
    {
        $mount->eggs()->detach($egg->id);

        Activity::event('admin:mount.detach-egg')
            ->subject($mount)
            ->property('egg', $egg->id)
            ->log();

        return $this->returnNoContent();
    }
}
