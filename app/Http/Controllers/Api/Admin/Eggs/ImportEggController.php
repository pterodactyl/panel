<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Eggs;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Eggs\ImportsEggs;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\ImportEggRequest;
use Pterodactyl\Http\Resources\Api\Admin\EggResource;
use Pterodactyl\Models\Egg;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Egg Sharing', 'Export, import, and update eggs from share files.')]
class ImportEggController extends AdminApiController
{
    /**
     * Import egg from uploaded payload.
     */
    #[Endpoint('Import egg', 'Creates an egg from an uploaded egg share JSON file.')]
    #[ResponseFromResource(EggResource::class, Egg::class, status: 201, description: 'Egg imported.', resourceKey: 'egg', meta: ['resource' => 'https://panel.example.com/api/admin/eggs/1'])]
    public function __invoke(ImportEggRequest $request, ImportsEggs $importer): JsonResponse
    {
        $egg = $importer->import($request->file('import_file'));

        Activity::event('admin:egg.import')
            ->subject($egg)
            ->property('name', $egg->name)
            ->log();

        return EggResource::make($egg)
            ->withMeta([
                'resource' => route('api.admin.eggs.view', [
                    'egg' => $egg->id,
                ]),
            ])
            ->respond(Response::HTTP_CREATED);
    }
}
