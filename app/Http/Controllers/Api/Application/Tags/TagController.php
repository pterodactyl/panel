<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Tags;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Tags\GetTagRequest;
use Pterodactyl\Http\Requests\Api\Application\Tags\GetTagsRequest;
use Pterodactyl\Models\Tag;
use Pterodactyl\Transformers\Api\Application\TagTransformer;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Tags', 'Retrieve the tags that group eggs and decide which nodes accept them. Requires read access to eggs.')]
class TagController extends ApplicationApiController
{
    /**
     * Return all tags that exist on the panel.
     *
     * @return ApiPayload
     */
    #[Endpoint('List tags', 'Returns a paginated list of the tags that group eggs.')]
    #[QueryParam('per_page', 'integer', 'Number of tags to return per page, up to 100.', required: false, example: 50)]
    #[QueryParam('filter[name]', 'string', 'Filter tags by name.', required: false, example: 'Minecraft')]
    #[QueryParam('filter[slug]', 'string', 'Filter tags by slug.', required: false, example: 'minecraft')]
    #[QueryParam('sort', 'string', 'Sort tags by id, name, or slug. Prefix with "-" for descending order.', required: false, example: 'name', enum: ['id', '-id', 'name', '-name', 'slug', '-slug'])]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "eggs" and "nodes" when the key can read those resources.', required: false, example: 'eggs', enum: ['eggs', 'nodes', 'eggs,nodes'])]
    #[ResponseFromTransformer(TagTransformer::class, Tag::class, collection: true, resourceKey: 'tag', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetTagsRequest $request): array
    {
        $tags = QueryBuilder::for(Tag::query())
            ->allowedFilters(['name', 'slug'])
            ->allowedSorts(['id', 'name', 'slug'])
            ->paginate($request->perPage());

        return Fractal::collection($tags)
            ->transformWith($this->getTransformer(TagTransformer::class))
            ->toResponseArray();
    }

    /**
     * Return a single tag.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get tag', 'Returns a single tag by internal numeric ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "eggs" and "nodes" when the key can read those resources.', required: false, example: 'eggs', enum: ['eggs', 'nodes', 'eggs,nodes'])]
    #[ResponseFromTransformer(TagTransformer::class, Tag::class, resourceKey: 'tag')]
    public function view(GetTagRequest $request, Tag $tag): array
    {
        return Fractal::item($tag)
            ->transformWith($this->getTransformer(TagTransformer::class))
            ->toResponseArray();
    }
}
