<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Eggs;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\ResponseField;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Data\EggCatalogEntry;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\GetEggsRequest;
use Pterodactyl\Services\Eggs\EggCatalogService;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Egg Catalog', 'Browse and import eggs from eggs.pterodactyl.io.')]
class EggCatalogController extends AdminApiController
{
    private const array CATALOG_EXAMPLE = [
        'data' => [[
            'id' => 'games-minecraft-paper',
            'name' => 'Paper',
            'description' => 'High performance Minecraft server.',
            'category' => 'games',
            'source_url' => 'https://eggs.pterodactyl.io/egg/games-minecraft-paper',
        ]],
    ];

    #[Endpoint('List egg catalog', 'Returns the cached Pterodactyl egg catalog for searching and filtering. The index is cached for one hour.')]
    #[ScribeResponse(self::CATALOG_EXAMPLE, description: 'Catalog entries returned.')]
    #[ScribeResponse(status: 502, description: 'The upstream catalog is unavailable or invalid.')]
    #[ResponseField('data', 'object[]', 'The available catalog entries.', required: true)]
    #[ResponseField('data.id', 'string', 'The catalog identifier used to import this egg.', required: true)]
    #[ResponseField('data.name', 'string', 'The egg name.', required: true)]
    #[ResponseField('data.description', 'string', 'The egg description, or an empty string.', required: true)]
    #[ResponseField('data.category', 'string', 'The catalog category, or an empty string.', required: true)]
    #[ResponseField('data.source_url', 'string', 'The egg page on eggs.pterodactyl.io.', required: true)]
    public function index(GetEggsRequest $request, EggCatalogService $catalog): JsonResponse
    {
        return new JsonResponse([
            'data' => array_map(fn (EggCatalogEntry $entry): array => $entry->toArray(), $catalog->all()),
        ]);
    }

    #[Endpoint('Refresh egg catalog', 'Clears the cached Pterodactyl catalog so the next listing downloads the current index.')]
    #[ScribeResponse(status: 204, description: 'Catalog cache cleared.')]
    public function refresh(GetEggsRequest $request, EggCatalogService $catalog): Response
    {
        $catalog->refresh();

        return $this->returnNoContent();
    }
}
