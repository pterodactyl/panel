<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Locations;

use Pterodactyl\Models\Location;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Services\Locations\LocationUpdateService;
use Pterodactyl\Services\Locations\LocationCreationService;
use Pterodactyl\Services\Locations\LocationDeletionService;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\LocationTransformer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Locations\GetLocationRequest;
use Pterodactyl\Http\Requests\Api\Application\Locations\GetLocationsRequest;
use Pterodactyl\Http\Requests\Api\Application\Locations\StoreLocationRequest;
use Pterodactyl\Http\Requests\Api\Application\Locations\DeleteLocationRequest;
use Pterodactyl\Http\Requests\Api\Application\Locations\UpdateLocationRequest;

class LocationController extends ApplicationApiController
{
    private LocationCreationService $creationService;
    private LocationDeletionService $deletionService;
    private LocationUpdateService $updateService;
    private LocationRepositoryInterface $repository;

    /**
     * LocationController constructor.
     */
    public function __construct(
        LocationCreationService $creationService,
        LocationDeletionService $deletionService,
        LocationUpdateService $updateService,
        LocationRepositoryInterface $repository
    ) {
        parent::__construct();

        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->updateService = $updateService;
        $this->repository = $repository;
    }

    /**
     * Return all of the locations currently registered on the Panel.
     */
    public function index(GetLocationsRequest $request): array
    {
        $perPage = $request->query('per_page', 10);
        if ($perPage < 1) {
            $perPage = 10;
        } elseif ($perPage > 100) {
            throw new BadRequestHttpException('"per_page" query parameter must be below 100.');
        }

        $locations = QueryBuilder::for(Location::query())
            ->allowedFilters(['short', 'long'])
            ->allowedSorts(['id'])
            ->paginate($perPage);

        return $this->fractal->collection($locations)
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->toArray();
    }

    /**
     * Return a single location.
     */
    public function view(GetLocationRequest $request, Location $location): array
    {
        return $this->fractal->item($location)
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->toArray();
    }

    /**
     * Store a new location on the Panel and return a HTTP/201 response code with the
     * new location attached.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(StoreLocationRequest $request): JsonResponse
    {
        $location = $this->creationService->handle($request->validated());

        return $this->fractal->item($location)
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
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function update(UpdateLocationRequest $request, Location $location): array
    {
        $location = $this->updateService->handle($location, $request->validated());

        return $this->fractal->item($location)
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->toArray();
    }

    /**
     * Delete a location from the Panel.
     *
     * @throws \Pterodactyl\Exceptions\Service\Location\HasActiveNodesException
     */
    public function delete(DeleteLocationRequest $request, Location $location): JsonResponse
    {
        $this->deletionService->handle($location);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
