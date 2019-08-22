<?php

namespace App\Http\Controllers\Api\Application\Locations;

use App\Models\Location;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Services\Locations\LocationUpdateService;
use App\Services\Locations\LocationCreationService;
use App\Services\Locations\LocationDeletionService;
use App\Contracts\Repository\LocationRepositoryInterface;
use App\Transformers\Api\Application\LocationTransformer;
use App\Http\Controllers\Api\Application\ApplicationApiController;
use App\Http\Requests\Api\Application\Locations\GetLocationRequest;
use App\Http\Requests\Api\Application\Locations\GetLocationsRequest;
use App\Http\Requests\Api\Application\Locations\StoreLocationRequest;
use App\Http\Requests\Api\Application\Locations\DeleteLocationRequest;
use App\Http\Requests\Api\Application\Locations\UpdateLocationRequest;

class LocationController extends ApplicationApiController
{
    /**
     * @var \App\Services\Locations\LocationCreationService
     */
    private $creationService;

    /**
     * @var \App\Services\Locations\LocationDeletionService
     */
    private $deletionService;

    /**
     * @var \App\Contracts\Repository\LocationRepositoryInterface
     */
    private $repository;

    /**
     * @var \App\Services\Locations\LocationUpdateService
     */
    private $updateService;

    /**
     * LocationController constructor.
     *
     * @param \App\Services\Locations\LocationCreationService       $creationService
     * @param \App\Services\Locations\LocationDeletionService       $deletionService
     * @param \App\Contracts\Repository\LocationRepositoryInterface $repository
     * @param \App\Services\Locations\LocationUpdateService         $updateService
     */
    public function __construct(
        LocationCreationService $creationService,
        LocationDeletionService $deletionService,
        LocationRepositoryInterface $repository,
        LocationUpdateService $updateService
    ) {
        parent::__construct();

        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Return all of the locations currently registered on the Panel.
     *
     * @param \App\Http\Requests\Api\Application\Locations\GetLocationsRequest $request
     * @return array
     */
    public function index(GetLocationsRequest $request): array
    {
        $locations = $this->repository->setSearchTerm($request->input('search'))->paginated(50);

        return $this->fractal->collection($locations)
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->toArray();
    }

    /**
     * Return a single location.
     *
     * @param \App\Http\Requests\Api\Application\Locations\GetLocationRequest $request
     * @return array
     */
    public function view(GetLocationRequest $request): array
    {
        return $this->fractal->item($request->getModel(Location::class))
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->toArray();
    }

    /**
     * Store a new location on the Panel and return a HTTP/201 response code with the
     * new location attached.
     *
     * @param \App\Http\Requests\Api\Application\Locations\StoreLocationRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
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
     * @param \App\Http\Requests\Api\Application\Locations\UpdateLocationRequest $request
     * @return array
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateLocationRequest $request): array
    {
        $location = $this->updateService->handle($request->getModel(Location::class), $request->validated());

        return $this->fractal->item($location)
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->toArray();
    }

    /**
     * Delete a location from the Panel.
     *
     * @param \App\Http\Requests\Api\Application\Locations\DeleteLocationRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\Service\Location\HasActiveNodesException
     */
    public function delete(DeleteLocationRequest $request): Response
    {
        $this->deletionService->handle($request->getModel(Location::class));

        return response('', 204);
    }
}
