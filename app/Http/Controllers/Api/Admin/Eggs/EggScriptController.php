<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Eggs;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Eggs\UpdatesEggInstallScripts;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\GetEggScriptRequest;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\UpdateEggScriptRequest;
use Pterodactyl\Http\Resources\Api\Admin\EggResource;
use Pterodactyl\Models\Egg;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Egg Scripts', 'Inspect and update egg install scripts.')]
class EggScriptController extends AdminApiController
{
    private const array INVALID_COPY_FROM_ERROR = [
        'errors' => [
            [
                'code' => 'InvalidCopyFromException',
                'status' => '400',
                'detail' => 'The selected egg cannot be used as an install script source.',
            ],
        ],
    ];

    /**
     * Show egg install script.
     */
    #[Endpoint('Get egg install script', 'Returns an egg with its install script block.')]
    #[ResponseFromResource(EggResource::class, Egg::class, description: 'Egg install script returned.', resourceKey: 'egg')]
    public function show(GetEggScriptRequest $request, Egg $egg): EggResource
    {

        return EggResource::make($egg);
    }

    /**
     * Update egg install script.
     */
    #[Endpoint('Update egg install script', 'Updates the install script configuration for an egg.')]
    #[ResponseFromResource(EggResource::class, Egg::class, description: 'Egg install script updated.', resourceKey: 'egg')]
    #[ScribeResponse(self::INVALID_COPY_FROM_ERROR, status: 400, description: 'The selected copy source is invalid.')]
    public function update(UpdateEggScriptRequest $request, UpdatesEggInstallScripts $scripts, Egg $egg): EggResource
    {
        $egg = $scripts->update($egg, $request->actionInput());

        Activity::event('admin:egg.scripts')
            ->subject($egg)
            ->property('name', $egg->name)
            ->log();

        return EggResource::make($egg);
    }
}
