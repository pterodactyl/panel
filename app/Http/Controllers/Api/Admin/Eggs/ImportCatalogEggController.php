<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Eggs;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Eggs\ImportsCatalogEggs;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\ImportCatalogEggRequest;
use Pterodactyl\Models\Egg;
use Pterodactyl\Transformers\Api\Admin\EggTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Egg Catalog', 'Browse and import eggs from eggs.pterodactyl.io.')]
class ImportCatalogEggController extends AdminApiController
{
    #[Endpoint('Import catalog egg', 'Downloads the selected entry from the Pterodactyl catalog and imports its egg definition and variables.')]
    #[ResponseFromTransformer(EggTransformer::class, Egg::class, status: 201, description: 'Egg imported.', resourceKey: 'egg', meta: ['resource' => 'https://panel.example.com/api/admin/eggs/1'])]
    #[ScribeResponse(status: 404, description: 'The selected catalog entry does not exist.')]
    #[ScribeResponse(status: 502, description: 'The upstream catalog or egg definition could not be loaded.')]
    public function __invoke(ImportCatalogEggRequest $request, ImportsCatalogEggs $catalogImporter): JsonResponse
    {
        $egg = $catalogImporter->import($request->catalogId());

        Activity::event('admin:egg.import')
            ->subject($egg)
            ->property('name', $egg->name)
            ->property('catalog_id', $request->catalogId())
            ->log();

        return Fractal::item($egg)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->addMeta(['resource' => route('api.admin.eggs.view', ['egg' => $egg->id])])
            ->respond(Response::HTTP_CREATED);
    }
}
