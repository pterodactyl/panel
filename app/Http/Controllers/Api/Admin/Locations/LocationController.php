<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Locations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Locations\CreatesLocations;
use Pterodactyl\Contracts\Locations\DeletesLocations;
use Pterodactyl\Contracts\Locations\UpdatesLocations;
use Pterodactyl\Extensions\Scribe\Attributes\ExtensionFieldsParam;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Locations\DeleteLocationRequest;
use Pterodactyl\Http\Requests\Api\Admin\Locations\GetLocationRequest;
use Pterodactyl\Http\Requests\Api\Admin\Locations\GetLocationsRequest;
use Pterodactyl\Http\Requests\Api\Admin\Locations\StoreLocationRequest;
use Pterodactyl\Http\Requests\Api\Admin\Locations\UpdateLocationRequest;
use Pterodactyl\Models\Location;
use Pterodactyl\Transformers\Api\Admin\LocationTransformer;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Locations', 'Create, update, retrieve, and delete physical or logical deployment locations.')]
class LocationController extends AdminApiController
{
    /**
     * List locations.
     *
     * @return ApiPayload
     */
    #[Endpoint('List locations', 'Returns a paginated list of locations.')]
    #[QueryParam('filter[short]', 'string', 'Filter locations by short identifier.', required: false, example: 'us-east')]
    #[QueryParam('filter[long]', 'string', 'Filter locations by long description.', required: false, example: 'US East')]
    #[QueryParam('sort', 'string', 'Sort locations by ID, short identifier, or creation date. Prefix with a hyphen for descending order.', required: false, example: 'short')]
    #[QueryParam('page', 'integer', 'Page number to return.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', required: false, example: 50)]
    #[ResponseFromTransformer(LocationTransformer::class, Location::class, description: 'Locations returned.', collection: true, resourceKey: 'location', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetLocationsRequest $request): array
    {
        $query = QueryBuilder::for(Location::query())
            ->allowedFilters(['short', 'long'])
            ->allowedSorts(['id', 'short', 'created_at']);

        $locations = $query->getEloquentBuilder()
            ->withCount(['nodes', 'servers'])
            ->paginate($request->perPage());

        return Fractal::collection($locations)
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->toResponseArray();
    }

    /**
     * Show location.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get location', 'Returns a single location by ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "nodes" and "servers".', required: false, example: 'nodes', enum: ['nodes', 'servers', 'nodes,servers'])]
    #[ResponseFromTransformer(LocationTransformer::class, Location::class, description: 'Location returned.', resourceKey: 'location', include: ['nodes'])]
    public function show(GetLocationRequest $request, Location $location): array
    {
        $location->loadCount(['nodes', 'servers']);
        $location->load(['nodes' => function (HasMany $query): void {
            $query->withCount('servers');
        }]);

        return Fractal::item($location)
            ->transformWith($this->getTransformer(LocationTransformer::class)->withExtensionFields())
            ->toResponseArray();
    }

    /**
     * Create location.
     */
    #[Endpoint('Create location', 'Creates a location for grouping nodes and servers.')]
    #[ResponseFromTransformer(LocationTransformer::class, Location::class, status: 201, description: 'Location created.', resourceKey: 'location', meta: ['resource' => 'https://panel.example.com/api/admin/locations/1'])]
    #[ExtensionFieldsParam]
    public function store(StoreLocationRequest $request, CreatesLocations $locations): JsonResponse
    {
        $location = $locations->create($request->payload());

        Activity::event('admin:location.create')
            ->subject($location)
            ->property('short', $location->short)
            ->log();

        return Fractal::item($location)
            ->transformWith($this->getTransformer(LocationTransformer::class)->withExtensionFields())
            ->addMeta([
                'resource' => route('api.admin.locations.view', [
                    'location' => $location->id,
                ]),
            ])
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Update location.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update location', 'Updates a location short identifier and description.')]
    #[ResponseFromTransformer(LocationTransformer::class, Location::class, description: 'Location updated.', resourceKey: 'location')]
    #[ExtensionFieldsParam]
    public function update(UpdateLocationRequest $request, UpdatesLocations $locations, Location $location): array
    {
        $location = $locations->update($location, $request->payload());

        Activity::event('admin:location.update')
            ->subject($location)
            ->property('short', $location->short)
            ->log();

        return Fractal::item($location)
            ->transformWith($this->getTransformer(LocationTransformer::class)->withExtensionFields())
            ->toResponseArray();
    }

    /**
     * Delete location.
     */
    #[Endpoint('Delete location', 'Deletes a location when no dependent resources prevent removal.')]
    #[ScribeResponse(status: 204, description: 'Location deleted.')]
    public function destroy(DeleteLocationRequest $request, DeletesLocations $locations, Location $location): Response
    {
        $locations->delete($location);

        Activity::event('admin:location.delete')
            ->subject($location)
            ->property('short', $location->short)
            ->log();

        return $this->returnNoContent();
    }
}
