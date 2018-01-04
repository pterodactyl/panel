<?php

namespace Pterodactyl\Http\Controllers\API\Admin\Locations;

use Spatie\Fractal\Fractal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Models\Location;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\LocationFormRequest;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Services\Locations\LocationUpdateService;
use Pterodactyl\Services\Locations\LocationCreationService;
use Pterodactyl\Services\Locations\LocationDeletionService;
use Pterodactyl\Transformers\Api\Admin\LocationTransformer;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

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
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        $locations = $this->repository->all(50);

        return $this->fractal->collection($locations)
            ->transformWith(new LocationTransformer($request))
            ->withResourceName('location')
            ->paginateWith(new IlluminatePaginatorAdapter($locations))
            ->toArray();
    }

    /**
     * Return a single location.
     *
     * @param \Illuminate\Http\Request     $request
     * @param \Pterodactyl\Models\Location $location
     * @return array
     */
    public function view(Request $request, Location $location): array
    {
        return $this->fractal->item($location)
            ->transformWith(new LocationTransformer($request))
            ->withResourceName('location')
            ->toArray();
    }

    /**
     * @param \Pterodactyl\Http\Requests\Admin\LocationFormRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(LocationFormRequest $request): JsonResponse
    {
        $location = $this->creationService->handle($request->normalize());

        return $this->fractal->item($location)
            ->transformWith(new LocationTransformer($request))
            ->withResourceName('location')
            ->respond(201);
    }

    /**
     * @param \Pterodactyl\Http\Requests\Admin\LocationFormRequest $request
     * @param \Pterodactyl\Models\Location                         $location
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(LocationFormRequest $request, Location $location): array
    {
        $location = $this->updateService->handle($location, $request->normalize());

        return $this->fractal->item($location)
            ->transformWith(new LocationTransformer($request))
            ->withResourceName('location')
            ->toArray();
    }

    /**
     * @param \Pterodactyl\Models\Location $location
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Service\Location\HasActiveNodesException
     */
    public function delete(Location $location): Response
    {
        $this->deletionService->handle($location);

        return response('', 204);
    }
}
