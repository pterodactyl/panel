<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Tags;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Tags\SyncsTags;
use Pterodactyl\Enum\EggSpecificTags;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Tags\DeleteTagRequest;
use Pterodactyl\Http\Requests\Api\Admin\Tags\GetTagRequest;
use Pterodactyl\Http\Requests\Api\Admin\Tags\GetTagsRequest;
use Pterodactyl\Http\Requests\Api\Admin\Tags\StoreTagRequest;
use Pterodactyl\Http\Requests\Api\Admin\Tags\SyncTagsRequest;
use Pterodactyl\Http\Requests\Api\Admin\Tags\UpdateTagRequest;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Transformers\Api\Admin\TagTransformer;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Tags', 'Generic labels attached to eggs and nodes, replacing the nest grouping.')]
class TagController extends AdminApiController
{
    private const array BUILT_IN_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'Built-in game tags cannot be edited.',
            ],
        ],
    ];

    /**
     * List tags.
     *
     * @return ApiPayload
     */
    #[Endpoint('List tags', 'Returns a paginated list of tags.')]
    #[QueryParam('filter[name]', 'string', 'Filter tags by name.', required: false, example: 'Minecraft')]
    #[QueryParam('filter[slug]', 'string', 'Filter tags by slug.', required: false, example: 'minecraft')]
    #[QueryParam('sort', 'string', 'Sort tags by id, name, or slug. Prefix with a hyphen for descending order.', required: false, example: 'name')]
    #[QueryParam('page', 'integer', 'Page number to return.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', required: false, example: 50)]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "eggs" and "nodes".', required: false, example: 'eggs', enum: ['eggs', 'nodes', 'eggs,nodes'])]
    #[ResponseFromTransformer(TagTransformer::class, Tag::class, description: 'Tags returned.', collection: true, resourceKey: 'tag', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetTagsRequest $request): array
    {
        $tags = QueryBuilder::for(Tag::query()->withCount('eggs', 'nodes'))
            ->allowedFilters(['name', 'slug'])
            ->allowedSorts(['id', 'name', 'slug'])
            ->paginate($request->perPage());

        return Fractal::collection($tags)
            ->transformWith($this->getTransformer(TagTransformer::class))
            ->toResponseArray();
    }

    /**
     * Show tag.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get tag', 'Returns a single tag by ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "eggs" and "nodes".', required: false, example: 'eggs', enum: ['eggs', 'nodes', 'eggs,nodes'])]
    #[ResponseFromTransformer(TagTransformer::class, Tag::class, description: 'Tag returned.', resourceKey: 'tag')]
    public function show(GetTagRequest $request, Tag $tag): array
    {
        $tag->loadCount('eggs', 'nodes');

        return Fractal::item($tag)
            ->transformWith($this->getTransformer(TagTransformer::class))
            ->toResponseArray();
    }

    /**
     * Create tag.
     */
    #[Endpoint('Create tag', 'Creates a tag.')]
    #[ResponseFromTransformer(TagTransformer::class, Tag::class, status: 201, description: 'Tag created.', resourceKey: 'tag', meta: ['resource' => 'https://panel.example.com/api/admin/tags/1'])]
    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = Tag::query()->create($request->validated());

        Activity::event('admin:tag.create')
            ->subject($tag)
            ->property('slug', $tag->slug)
            ->log();

        return Fractal::item($tag)
            ->transformWith($this->getTransformer(TagTransformer::class))
            ->addMeta([
                'resource' => route('api.admin.tags.view', [
                    'tag' => $tag->id,
                ]),
            ])
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Update tag.
     *
     *
     * @return ApiPayload
     *
     * @throws DisplayException
     */
    #[Endpoint('Update tag', 'Updates an existing tag. Built-in game tags cannot be edited.')]
    #[ResponseFromTransformer(TagTransformer::class, Tag::class, description: 'Tag updated.', resourceKey: 'tag')]
    #[ScribeResponse(self::BUILT_IN_ERROR, status: 400, description: 'The tag is a built-in game tag.')]
    public function update(UpdateTagRequest $request, Tag $tag): array
    {
        $this->guardBuiltIn($tag);

        $tag->update($request->validated());

        Activity::event('admin:tag.update')
            ->subject($tag)
            ->property('slug', $tag->slug)
            ->log();

        return Fractal::item($tag)
            ->transformWith($this->getTransformer(TagTransformer::class))
            ->toResponseArray();
    }

    /**
     * Delete tag.
     *
     * Built-in game tags cannot be deleted because the cascading pivot cleanup would
     * discard every egg and node assignment. Recreating the tag later cannot restore
     * those associations.
     *
     * @throws DisplayException
     */
    #[Endpoint('Delete tag', 'Deletes a tag and detaches it from every egg and node.')]
    #[ScribeResponse(status: 204, description: 'Tag deleted.')]
    #[ScribeResponse(self::BUILT_IN_ERROR, status: 400, description: 'The tag is a built-in game tag.')]
    public function destroy(DeleteTagRequest $request, Tag $tag): Response
    {
        $this->guardBuiltIn($tag);

        $tag->delete();

        Activity::event('admin:tag.delete')
            ->subject($tag)
            ->property('slug', $tag->slug)
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Replace the full set of tags on an egg.
     *
     * @return ApiPayload
     */
    #[Endpoint('Sync egg tags', 'Replaces the tags applied to an egg. Values may be tag IDs or new tag names.')]
    #[ResponseFromTransformer(TagTransformer::class, Tag::class, description: 'Tags applied to the egg.', collection: true, resourceKey: 'tag')]
    public function syncEgg(SyncTagsRequest $request, SyncsTags $sync, Egg $egg): array
    {
        $sync->syncEgg($egg, $request->tagValues());

        Activity::event('admin:egg.tags')
            ->subject($egg)
            ->property('tags', JsonValueGuard::stringList($egg->tags()->pluck('slug')->all()))
            ->log();

        return Fractal::collection($egg->tags()->get())
            ->transformWith($this->getTransformer(TagTransformer::class))
            ->toResponseArray();
    }

    /**
     * Replace the games a node accepts (the 'egg'-kind attachments). A node's
     * reservations are managed separately by syncNodeDeploymentTags(), and syncing
     * one kind never disturbs the other.
     *
     * @return ApiPayload
     */
    #[Endpoint('Sync node game tags', 'Replaces the games a node accepts. A node with no game tags accepts any egg.')]
    #[ResponseFromTransformer(TagTransformer::class, Tag::class, description: 'Games the node accepts.', collection: true, resourceKey: 'tag')]
    public function syncNode(SyncTagsRequest $request, SyncsTags $sync, Node $node): array
    {
        $sync->syncNodeGames($node, $request->tagValues());

        Activity::event('admin:node.tags')
            ->subject($node)
            ->property('tags', JsonValueGuard::stringList($node->eggTags()->pluck('slug')->all()))
            ->log();

        return Fractal::collection($node->eggTags()->get())
            ->transformWith($this->getTransformer(TagTransformer::class))
            ->toResponseArray();
    }

    /**
     * Replace the reservations on a node (the 'deployment'-kind attachments). A
     * deploy only lands on this node when it declares exactly these tags.
     *
     * @return ApiPayload
     */
    #[Endpoint('Sync node deployment tags', 'Replaces the reservations on a node. A deploy must declare exactly these tags to land here.')]
    #[ResponseFromTransformer(TagTransformer::class, Tag::class, description: 'Reservations on the node.', collection: true, resourceKey: 'tag')]
    public function syncNodeDeploymentTags(SyncTagsRequest $request, SyncsTags $sync, Node $node): array
    {
        $sync->syncNodeDeployments($node, $request->tagValues());

        Activity::event('admin:node.deployment-tags')
            ->subject($node)
            ->property('tags', JsonValueGuard::stringList($node->deploymentTags()->pluck('slug')->all()))
            ->log();

        return Fractal::collection($node->deploymentTags()->get())
            ->transformWith($this->getTransformer(TagTransformer::class))
            ->toResponseArray();
    }

    /**
     * Reject edits to a built-in game tag. Its slug is the key that feature gating
     * matches on, and its presentation comes from the EggSpecificTags enum, so it
     * must never be renamed or relabelled.
     *
     * @throws DisplayException
     */
    private function guardBuiltIn(Tag $tag): void
    {
        throw_if(EggSpecificTags::isSpecial($tag->slug), DisplayException::class, 'Built-in game tags cannot be edited.');
    }
}
