<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Mounts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Mounts\DeleteMountRequest;
use Pterodactyl\Http\Requests\Api\Admin\Mounts\GetMountRequest;
use Pterodactyl\Http\Requests\Api\Admin\Mounts\GetMountsRequest;
use Pterodactyl\Http\Requests\Api\Admin\Mounts\StoreMountRequest;
use Pterodactyl\Http\Requests\Api\Admin\Mounts\UpdateMountRequest;
use Pterodactyl\Models\Mount;
use Pterodactyl\Transformers\Api\Admin\MountTransformer;
use Ramsey\Uuid\Uuid;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Mounts', 'Create and manage mount definitions and their egg or node attachments.')]
class MountController extends AdminApiController
{
    public const array MOUNT_EXAMPLE = [
        'object' => 'mount',
        'attributes' => [
            'id' => 1,
            'uuid' => '1b19cf3f-2f89-4f88-a81e-321e7fe326bc',
            'name' => 'Shared Mods',
            'description' => 'Shared mod files for servers.',
            'source' => '/mnt/shared-mods',
            'target' => '/home/container/mods',
            'read_only' => true,
            'user_mountable' => false,
            'eggs_count' => 1,
            'nodes_count' => 1,
            'servers_count' => 0,
        ],
    ];

    /**
     * List mounts.
     *
     * @return ApiPayload
     */
    #[Endpoint('List mounts', 'Returns a paginated list of mount definitions.')]
    #[QueryParam('filter[name]', 'string', 'Filter mounts by name.', required: false, example: 'Shared Mods')]
    #[QueryParam('sort', 'string', 'Sort mounts by id or name. Prefix with a hyphen for descending order.', required: false, example: 'name')]
    #[QueryParam('page', 'integer', 'Page number to return.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', required: false, example: 50)]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "eggs", "nodes", and "servers".', required: false, example: 'eggs,nodes', enum: ['eggs', 'nodes', 'servers', 'eggs,nodes', 'eggs,nodes,servers'])]
    #[ResponseFromTransformer(MountTransformer::class, Mount::class, description: 'Mounts returned.', collection: true, resourceKey: 'mount', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetMountsRequest $request): array
    {
        $mounts = QueryBuilder::for(Mount::query()->withCount('eggs', 'nodes', 'servers'))
            ->allowedFilters(['name'])
            ->allowedSorts(['id', 'name'])
            ->paginate($request->perPage());

        return Fractal::collection($mounts)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->toResponseArray();
    }

    /**
     * Show mount.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get mount', 'Returns a single mount definition by ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "eggs", "nodes", and "servers".', required: false, example: 'eggs,nodes', enum: ['eggs', 'nodes', 'servers', 'eggs,nodes', 'eggs,nodes,servers'])]
    #[ResponseFromTransformer(MountTransformer::class, Mount::class, description: 'Mount returned.', resourceKey: 'mount', include: ['eggs', 'nodes', 'servers'])]
    public function show(GetMountRequest $request, Mount $mount): array
    {
        $mount->loadCount('eggs', 'nodes', 'servers');

        return Fractal::item($mount)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->toResponseArray();
    }

    /**
     * Create mount.
     */
    #[Endpoint('Create mount', 'Creates a mount definition and generates its UUID.')]
    #[ResponseFromTransformer(MountTransformer::class, Mount::class, status: 201, description: 'Mount created.', resourceKey: 'mount', meta: ['resource' => 'https://panel.example.com/api/admin/mounts/1'])]
    public function store(StoreMountRequest $request): JsonResponse
    {
        $mount = (new Mount)->fill($request->validated());
        $mount->forceFill(['uuid' => Uuid::uuid4()->toString()]);
        $mount->saveOrFail();

        Activity::event('admin:mount.create')
            ->subject($mount)
            ->property('name', $mount->name)
            ->log();

        return Fractal::item($mount)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->addMeta([
                'resource' => route('api.admin.mounts.view', [
                    'mount' => $mount->id,
                ]),
            ])
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Update mount.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update mount', 'Updates an existing mount definition.')]
    #[ResponseFromTransformer(MountTransformer::class, Mount::class, description: 'Mount updated.', resourceKey: 'mount')]
    public function update(UpdateMountRequest $request, Mount $mount): array
    {
        $mount->forceFill($request->validated())->save();

        Activity::event('admin:mount.update')
            ->subject($mount)
            ->property('name', $mount->name)
            ->log();

        return Fractal::item($mount)
            ->transformWith($this->getTransformer(MountTransformer::class))
            ->toResponseArray();
    }

    /**
     * Delete mount.
     */
    #[Endpoint('Delete mount', 'Deletes a mount definition.')]
    #[ScribeResponse(status: 204, description: 'Mount deleted.')]
    public function destroy(DeleteMountRequest $request, Mount $mount): Response
    {
        $name = $mount->name;

        $mount->delete();

        Activity::event('admin:mount.delete')
            ->subject($mount)
            ->property('name', $name)
            ->log();

        return $this->returnNoContent();
    }
}
