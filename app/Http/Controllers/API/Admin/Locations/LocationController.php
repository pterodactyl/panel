<?php

namespace Pterodactyl\Http\Controllers\API\Admin\Locations;

use Spatie\Fractal\Fractal;
use Illuminate\Http\Response;
use Pterodactyl\Models\Location;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Services\Locations\LocationUpdateService;
use Pterodactyl\Services\Locations\LocationCreationService;
use Pterodactyl\Services\Locations\LocationDeletionService;
use Pterodactyl\Transformers\Api\Admin\LocationTransformer;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Http\Requests\API\Admin\Locations\GetLocationsRequest;
use Pterodactyl\Http\Requests\API\Admin\Locations\DeleteLocationRequest;
use Pterodactyl\Http\Requests\API\Admin\Locations\UpdateLocationRequest;

class LocationController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Locations\LocationCreationService
     */
    private $creationService;

    /**
     * @var \Pterodactyl\Services\Locations\LocationDeletionService
     */
    private $deletionService;

    /**
     * @var \Spatie\Fractal\Fractal
     */
    private $fractal;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Locations\LocationUpdateService
     */
    private $updateService;

    /**
     * LocationController constructor.
     *
     * @param \Spatie\Fractal\Fractal                                       $fractal
     * @param \Pterodactyl\Services\Locations\LocationCreationService       $creationService
     * @param \Pterodactyl\Services\Locations\LocationDeletionService       $deletionService
     * @param \Pterodactyl\Contracts\Repository\LocationRepositoryInterface $repository
     * @param \Pterodactyl\Services\Locations\LocationUpdateService         $updateService
     */
    public function __construct(
        Fractal $fractal,
        LocationCreationService $creationService,
        LocationDeletionService $deletionService,
        LocationRepositoryInterface $repository,
        LocationUpdateService $updateService
    ) {
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->fractal = $fractal;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Return all of the locations currently registered on the Panel.
     *
     * @param \Pterodactyl\Http\Requests\API\Admin\Locations\GetLocationsRequest $request
     * @return array
     */
    public function index(GetLocationsRequest $request): array
    {
        $locations = $this->repository->paginated(100);

        return $this->fractal->collection($locations)
            ->transformWith((new LocationTransformer)->setKey($request->key()))
            ->withResourceName('location')
            ->paginateWith(new IlluminatePaginatorAdapter($locations))
            ->toArray();
    }

    /**
     * Return a single location.
     *
     * @param \Pterodactyl\Http\Controllers\API\Admin\Locations\GetLocationRequest $request
     * @param \Pterodactyl\Models\Location                                         $location
     * @return array
     */
    public function view(GetLocationRequest $request, Location $location): array
    {
        return $this->fractal->item($location)
            ->transformWith((new LocationTransformer)->setKey($request->key()))
            ->withResourceName('location')
            ->toArray();
    }

    /**
     * Store a new location on the Panel and return a HTTP/201 response code with the
     * new location attached.
     *
     * @param \Pterodactyl\Http\Controllers\API\Admin\Locations\StoreLocationRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(StoreLocationRequest $request): JsonResponse
    {
        $location = $this->creationService->handle($request->validated());

        return $this->fractal->item($location)
            ->transformWith((new LocationTransformer)->setKey($request->key()))
            ->withResourceName('location')
            ->respond(201);
    }

    /**
     * Update a location on the Panel and return the updated record to the user.
     *
     * @param \Pterodactyl\Http\Requests\API\Admin\Locations\UpdateLocationRequest $request
     * @param \Pterodactyl\Models\Location                                         $location
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateLocationRequest $request, Location $location): array
    {
        $location = $this->updateService->handle($location, $request->validated());

        return $this->fractal->item($location)
            ->transformWith((new LocationTransformer)->setKey($request->key()))
            ->withResourceName('location')
            ->toArray();
    }

    /**
     * Delete a location from the Panel.
     *
     * @param \Pterodactyl\Http\Requests\API\Admin\Locations\DeleteLocationRequest $request
     * @param \Pterodactyl\Models\Location                                         $location
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Service\Location\HasActiveNodesException
     */
    public function delete(DeleteLocationRequest $request, Location $location): Response
    {
        $this->deletionService->handle($location);

        return response('', 204);
    }
}
