<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Eggs;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Eggs\UpdatesEggsFromImports;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\UpdateImportEggRequest;
use Pterodactyl\Models\Egg;
use Pterodactyl\Transformers\Api\Admin\EggTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Egg Sharing', 'Export, import, and update eggs from share files.')]
class UpdateImportEggController extends AdminApiController
{
    /**
     * Import updates into existing egg.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update egg from import', 'Updates an existing egg from an uploaded egg share JSON file.')]
    #[ResponseFromTransformer(EggTransformer::class, Egg::class, description: 'Egg updated from import.', resourceKey: 'egg')]
    public function __invoke(UpdateImportEggRequest $request, UpdatesEggsFromImports $updater, Egg $egg): array
    {
        $egg = $updater->update($egg, $request->file('import_file'));

        Activity::event('admin:egg.update-import')
            ->subject($egg)
            ->property('name', $egg->name)
            ->log();

        return Fractal::item($egg)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toResponseArray();
    }
}
