<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Eggs;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Eggs\UpdatesEggInstallScripts;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\GetEggScriptRequest;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\UpdateEggScriptRequest;
use Pterodactyl\Models\Egg;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Transformers\Api\Admin\EggTransformer;

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
     *
     * @return ApiPayload
     */
    #[Endpoint('Get egg install script', 'Returns an egg with its install script block.')]
    #[ResponseFromTransformer(EggTransformer::class, Egg::class, description: 'Egg install script returned.', resourceKey: 'egg')]
    public function show(GetEggScriptRequest $request, Egg $egg): array
    {

        return Fractal::item($egg)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toResponseArray();
    }

    /**
     * Update egg install script.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update egg install script', 'Updates the install script configuration for an egg.')]
    #[ResponseFromTransformer(EggTransformer::class, Egg::class, description: 'Egg install script updated.', resourceKey: 'egg')]
    #[ScribeResponse(self::INVALID_COPY_FROM_ERROR, status: 400, description: 'The selected copy source is invalid.')]
    public function update(UpdateEggScriptRequest $request, UpdatesEggInstallScripts $scripts, Egg $egg): array
    {

        $data = $request->validated();
        JsonValueGuard::assertPayload9($data);
        $egg = $scripts->update($egg, $data);

        Activity::event('admin:egg.scripts')
            ->subject($egg)
            ->property('name', $egg->name)
            ->log();

        return Fractal::item($egg)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toResponseArray();
    }
}
