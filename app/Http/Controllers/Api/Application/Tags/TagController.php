<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Tags;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Tags\GetTagRequest;
use Pterodactyl\Http\Requests\Api\Application\Tags\GetTagsRequest;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Application\TagResource;
use Pterodactyl\Models\Tag;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Tags', 'Retrieve the tags that group eggs and decide which nodes accept them. Requires read access to eggs.')]
class TagController extends ApplicationApiController
{
    /**
     * Return all tags that exist on the panel.
     */
    #[Endpoint('List tags', 'Returns a paginated list of the tags that group eggs.')]
    #[QueryParam('per_page', 'integer', 'Number of tags to return per page, up to 100.', required: false, example: 50)]
    #[QueryParam('filter[name]', 'string', 'Filter tags by name.', required: false, example: 'Minecraft')]
    #[QueryParam('filter[slug]', 'string', 'Filter tags by slug.', required: false, example: 'minecraft')]
    #[QueryParam('sort', 'string', 'Sort tags by id, name, or slug. Prefix with "-" for descending order.', required: false, example: 'name', enum: ['id', '-id', 'name', '-name', 'slug', '-slug'])]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "eggs" and "nodes" when the key can read those resources.', required: false, example: 'eggs', enum: ['eggs', 'nodes', 'eggs,nodes'])]
    #[ResponseFromResource(TagResource::class, Tag::class, collection: true, resourceKey: 'tag', paginate: 50)]
    public function index(GetTagsRequest $request): ApiResourceCollection
    {
        $tags = QueryBuilder::for(Tag::query())
            ->allowedFilters(['name', 'slug'])
            ->allowedSorts(['id', 'name', 'slug'])
            ->paginate($request->perPage());

        return TagResource::collection($tags);
    }

    /**
     * Return a single tag.
     */
    #[Endpoint('Get tag', 'Returns a single tag by internal numeric ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "eggs" and "nodes" when the key can read those resources.', required: false, example: 'eggs', enum: ['eggs', 'nodes', 'eggs,nodes'])]
    #[ResponseFromResource(TagResource::class, Tag::class, resourceKey: 'tag')]
    public function view(GetTagRequest $request, Tag $tag): TagResource
    {
        return TagResource::make($tag);
    }
}
