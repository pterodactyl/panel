<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Eggs;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Eggs\GetEggRequest;
use Pterodactyl\Http\Requests\Api\Application\Eggs\GetEggsRequest;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Application\EggResource;
use Pterodactyl\Models\Egg;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Eggs', 'Retrieve egg metadata used for server creation.')]
class EggController extends ApplicationApiController
{
    /**
     * Return all eggs that exist on the panel.
     */
    #[Endpoint('List eggs', 'Returns a paginated list of server eggs available on the panel.')]
    #[QueryParam('per_page', 'integer', 'Number of eggs to return per page, up to 100.', required: false, example: 50)]
    #[QueryParam('filter[name]', 'string', 'Filter eggs by name.', required: false, example: 'Paper')]
    #[QueryParam('filter[tag]', 'string', 'Filter eggs by tag slug. Separate several slugs with commas to return eggs that carry any of them.', required: false, example: 'minecraft')]
    #[QueryParam('sort', 'string', 'Sort eggs by id or name. Prefix with "-" for descending order.', required: false, example: 'name', enum: ['id', '-id', 'name', '-name'])]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "servers", "config", "script", "variables", and "tags" when the key can read those resources.', required: false, example: 'variables', enum: ['servers', 'config', 'script', 'variables', 'tags'])]
    #[ResponseFromResource(EggResource::class, Egg::class, collection: true, resourceKey: 'egg', paginate: 50)]
    public function index(GetEggsRequest $request): ApiResourceCollection
    {
        $eggs = QueryBuilder::for(Egg::query())
            ->allowedFilters([
                'name',
                AllowedFilter::exact('tag', 'tags.slug'),
            ])
            ->allowedSorts(['id', 'name'])
            ->paginate($request->perPage());

        return EggResource::collection($eggs);
    }

    /**
     * Return a single egg.
     */
    #[Endpoint('Get egg', 'Returns a single egg by internal numeric ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "servers", "config", "script", "variables", and "tags" when the key can read those resources.', required: false, example: 'variables', enum: ['servers', 'config', 'script', 'variables', 'tags'])]
    #[ResponseFromResource(EggResource::class, Egg::class, resourceKey: 'egg')]
    public function view(GetEggRequest $request, Egg $egg): EggResource
    {
        return EggResource::make($egg);
    }
}
