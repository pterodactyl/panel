<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Eggs;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Eggs\CreatesEggs;
use Pterodactyl\Contracts\Eggs\DeletesEggs;
use Pterodactyl\Contracts\Eggs\UpdatesEggs;
use Pterodactyl\Extensions\Scribe\Attributes\ExtensionFieldsParam;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\DeleteEggRequest;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\GetEggRequest;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\GetEggsRequest;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\StoreEggRequest;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\UpdateEggRequest;
use Pterodactyl\Models\Egg;
use Pterodactyl\Transformers\Api\Admin\EggTransformer;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Eggs', 'Create and manage server egg definitions.')]
class EggController extends AdminApiController
{
    private const array DELETE_CONFLICT_ERROR = [
        'errors' => [
            [
                'code' => 'HasActiveServersException',
                'status' => '400',
                'detail' => 'Cannot delete an egg with active servers attached to it.',
            ],
        ],
    ];

    /**
     * List eggs.
     *
     * @return ApiPayload
     */
    #[Endpoint('List eggs', 'Returns a paginated list of egg definitions.')]
    #[QueryParam('filter[name]', 'string', 'Filter eggs by name.', required: false, example: 'Paper')]
    #[QueryParam('sort', 'string', 'Sort eggs by id or name. Prefix with a hyphen for descending order.', required: false, example: 'name')]
    #[QueryParam('page', 'integer', 'Page number to return.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', required: false, example: 50)]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include: variables, tags, servers.', required: false, example: 'variables', enum: ['variables', 'tags', 'servers', 'variables,tags', 'variables,servers', 'tags,servers', 'variables,tags,servers'])]
    #[ResponseFromTransformer(EggTransformer::class, Egg::class, description: 'Eggs returned.', collection: true, resourceKey: 'egg', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetEggsRequest $request): array
    {
        $eggs = QueryBuilder::for(Egg::query())
            ->allowedFilters(['name'])
            ->allowedSorts(['id', 'name'])
            ->paginate($request->perPage());

        return Fractal::collection($eggs)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toResponseArray();
    }

    /**
     * Show egg.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get egg', 'Returns a single egg definition by ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include: variables, tags, servers.', required: false, example: 'variables,tags', enum: ['variables', 'tags', 'servers', 'variables,tags', 'variables,servers', 'tags,servers', 'variables,tags,servers'])]
    #[ResponseFromTransformer(EggTransformer::class, Egg::class, description: 'Egg returned.', resourceKey: 'egg', include: ['servers', 'variables', 'tags'])]
    public function show(GetEggRequest $request, Egg $egg): array
    {
        return Fractal::item($egg)
            ->transformWith($this->getTransformer(EggTransformer::class)->withExtensionFields())
            ->toResponseArray();
    }

    /**
     * Create egg.
     */
    #[Endpoint('Create egg', 'Creates an egg definition.')]
    #[ResponseFromTransformer(EggTransformer::class, Egg::class, status: 201, description: 'Egg created.', resourceKey: 'egg', meta: ['resource' => 'https://panel.example.com/api/admin/eggs/1'])]
    #[ExtensionFieldsParam]
    public function store(StoreEggRequest $request, CreatesEggs $eggs): JsonResponse
    {
        $egg = $eggs->create($request->payload());

        Activity::event('admin:egg.create')
            ->subject($egg)
            ->property('name', $egg->name)
            ->log();

        return Fractal::item($egg)
            ->transformWith($this->getTransformer(EggTransformer::class)->withExtensionFields())
            ->addMeta([
                'resource' => route('api.admin.eggs.view', ['egg' => $egg->id]),
            ])
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Update egg.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update egg', 'Updates an egg definition.')]
    #[ResponseFromTransformer(EggTransformer::class, Egg::class, description: 'Egg updated.', resourceKey: 'egg')]
    #[ExtensionFieldsParam]
    public function update(UpdateEggRequest $request, UpdatesEggs $eggs, Egg $egg): array
    {
        $egg = $eggs->update($egg, $request->payload());

        Activity::event('admin:egg.update')
            ->subject($egg)
            ->property('name', $egg->name)
            ->log();

        return Fractal::item($egg)
            ->transformWith($this->getTransformer(EggTransformer::class)->withExtensionFields())
            ->toResponseArray();
    }

    /**
     * Delete egg.
     */
    #[Endpoint('Delete egg', 'Deletes an egg definition.')]
    #[ScribeResponse(status: 204, description: 'Egg deleted.')]
    #[ScribeResponse(self::DELETE_CONFLICT_ERROR, status: 400, description: 'The egg has active servers or child eggs.')]
    public function destroy(DeleteEggRequest $request, DeletesEggs $eggs, Egg $egg): Response
    {
        $eggs->delete($egg);

        Activity::event('admin:egg.delete')
            ->subject($egg)
            ->property('name', $egg->name)
            ->log();

        return $this->returnNoContent();
    }
}
