<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Locations;

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
use Pterodactyl\Exceptions\Service\Location\HasActiveNodesException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Locations\DeleteLocationRequest;
use Pterodactyl\Http\Requests\Api\Application\Locations\GetLocationRequest;
use Pterodactyl\Http\Requests\Api\Application\Locations\GetLocationsRequest;
use Pterodactyl\Http\Requests\Api\Application\Locations\StoreLocationRequest;
use Pterodactyl\Http\Requests\Api\Application\Locations\UpdateLocationRequest;
use Pterodactyl\Models\Location;
use Pterodactyl\Transformers\Api\Application\LocationTransformer;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Locations', 'Create, update, retrieve, and delete physical or logical deployment locations.')]
class LocationController extends ApplicationApiController
{
    private const array ACTIVE_NODES_ERROR = [
        'errors' => [
            [
                'code' => 'HasActiveNodesException',
                'status' => '400',
                'detail' => 'Cannot delete a location that has active nodes attached to it.',
            ],
        ],
    ];

    /**
     * Return all the locations currently registered on the Panel.
     *
     * @return ApiPayload
     */
    #[Endpoint('List locations', 'Returns a paginated list of all locations registered on the panel.')]
    #[QueryParam('per_page', 'integer', 'Number of locations to return per page, up to 100.', required: false, example: 50)]
    #[QueryParam('filter[short]', 'string', 'Filter locations by short identifier.', required: false, example: 'us-east')]
    #[QueryParam('filter[long]', 'string', 'Filter locations by description.', required: false, example: 'US East Datacenter')]
    #[QueryParam('sort', 'string', 'Sort locations by id. Prefix with "-" for descending order.', required: false, example: 'id', enum: ['id', '-id'])]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "nodes" and "servers" when the key can read those resources.', required: false, example: 'nodes,servers', enum: ['nodes', 'servers', 'nodes,servers'])]
    #[ResponseFromTransformer(LocationTransformer::class, Location::class, collection: true, resourceKey: 'location', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetLocationsRequest $request): array
    {
        $locations = QueryBuilder::for(Location::query())
            ->allowedFilters(['short', 'long'])
            ->allowedSorts(['id'])
            ->paginate($request->perPage());

        return Fractal::collection($locations)
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->toResponseArray();
    }

    /**
     * Return a single location.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get location', 'Returns a single location by internal numeric ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "nodes" and "servers" when the key can read those resources.', required: false, example: 'nodes,servers', enum: ['nodes', 'servers', 'nodes,servers'])]
    #[ResponseFromTransformer(LocationTransformer::class, Location::class, resourceKey: 'location')]
    public function view(GetLocationRequest $request, Location $location): array
    {
        return Fractal::item($location)
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->toResponseArray();
    }

    /**
     * Store a new location on the Panel and return an HTTP/201 response code with the
     * new location attached.
     */
    #[Endpoint('Create location', 'Creates a new location and returns the created resource.')]
    #[ResponseFromTransformer(LocationTransformer::class, Location::class, status: 201, description: 'Location created.', resourceKey: 'location', meta: ['resource' => 'https://panel.example.test/api/application/locations/1'])]
    public function store(StoreLocationRequest $request, CreatesLocations $locations): JsonResponse
    {
        $location = $locations->create($request->payload());

        return Fractal::item($location)
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->addMeta([
                'resource' => route('api.application.locations.view', [
                    'location' => $location->id,
                ]),
            ])
            ->respond(201);
    }

    /**
     * Update a location on the Panel and return the updated record to the user.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update location', 'Updates an existing location by internal numeric ID.')]
    #[ResponseFromTransformer(LocationTransformer::class, Location::class, resourceKey: 'location')]
    public function update(UpdateLocationRequest $request, UpdatesLocations $locations, Location $location): array
    {
        $location = $locations->update($location, $request->payload());

        return Fractal::item($location)
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->toResponseArray();
    }

    /**
     * Delete a location from the Panel.
     *
     * @throws HasActiveNodesException
     */
    #[Endpoint('Delete location', 'Deletes a location by internal numeric ID.')]
    #[ScribeResponse(status: 204, description: 'Location deleted.')]
    #[ScribeResponse(self::ACTIVE_NODES_ERROR, status: 400, description: 'The location has active nodes attached.')]
    public function delete(DeleteLocationRequest $request, DeletesLocations $locations, Location $location): Response
    {
        $locations->delete($location);

        return response('', 204);
    }
}
