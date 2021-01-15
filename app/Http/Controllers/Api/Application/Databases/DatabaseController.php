<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Databases;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\DatabaseHost;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Services\Databases\Hosts\HostUpdateService;
use Pterodactyl\Services\Databases\Hosts\HostCreationService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Transformers\Api\Application\DatabaseHostTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Databases\GetDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Application\Databases\GetDatabasesRequest;
use Pterodactyl\Http\Requests\Api\Application\Databases\DatabaseNodesRequest;
use Pterodactyl\Http\Requests\Api\Application\Databases\StoreDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Application\Databases\UpdateDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Application\Databases\DeleteDatabaseRequest;

class DatabaseController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Services\Databases\Hosts\HostCreationService
     */
    private $creationService;

    /**
     * @var \Pterodactyl\Services\Databases\Hosts\HostUpdateService
     */
    private $updateService;

    /**
     * DatabaseController constructor.
     *
     * @param \Pterodactyl\Services\Databases\Hosts\HostCreationService $creationService
     * @param \Pterodactyl\Services\Databases\Hosts\HostUpdateService $updateService
     */
    public function __construct(HostCreationService $creationService, HostUpdateService $updateService)
    {
        parent::__construct();

        $this->creationService = $creationService;
        $this->updateService = $updateService;
    }

    /**
     * Returns an array of all database hosts.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Databases\GetDatabasesRequest $request
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetDatabasesRequest $request): array
    {
        $perPage = $request->query('per_page', 10);
        if ($perPage < 1) {
            $perPage = 10;
        } else if ($perPage > 100) {
            throw new BadRequestHttpException('"per_page" query parameter must be below 100.');
        }

        $databases = QueryBuilder::for(DatabaseHost::query())
            ->allowedFilters(['name', 'host'])
            ->allowedSorts(['id', 'name', 'host'])
            ->paginate($perPage);

        return $this->fractal->collection($databases)
            ->transformWith($this->getTransformer(DatabaseHostTransformer::class))
            ->toArray();
    }

    /**
     * Returns a single database host.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Databases\GetDatabaseRequest $request
     * @param \Pterodactyl\Models\DatabaseHost $databaseHost
     *
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(GetDatabaseRequest $request, DatabaseHost $databaseHost): array
    {
        return $this->fractal->item($databaseHost)
            ->transformWith($this->getTransformer(DatabaseHostTransformer::class))
            ->toArray();
    }

    /**
     * Creates a new database host.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Databases\StoreDatabaseRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function store(StoreDatabaseRequest $request): JsonResponse
    {
        $databaseHost = $this->creationService->handle($request->validated());

        return $this->fractal->item($databaseHost)
            ->transformWith($this->getTransformer(DatabaseHostTransformer::class))
            ->respond(JsonResponse::HTTP_CREATED);
    }

    /**
     * Updates a database host.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Databases\UpdateDatabaseRequest $request
     * @param \Pterodactyl\Models\DatabaseHost $databaseHost
     *
     * @return array
     * @throws \Throwable
     */
    public function update(UpdateDatabaseRequest $request, DatabaseHost $databaseHost): array
    {
        $databaseHost = $this->updateService->handle($databaseHost->id, $request->validated());

        return $this->fractal->item($databaseHost)
            ->transformWith($this->getTransformer(DatabaseHostTransformer::class))
            ->toArray();
    }

    /**
     * Deletes a database host.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Databases\DeleteDatabaseRequest $request
     * @param \Pterodactyl\Models\DatabaseHost $databaseHost
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function delete(DeleteDatabaseRequest $request, DatabaseHost $databaseHost): JsonResponse
    {
        $databaseHost->delete();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * ?
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Databases\DatabaseNodesRequest $request
     * @param \Pterodactyl\Models\Mount $mount
     *
     * @return array
     */
    public function addNodes(DatabaseNodesRequest $request, DatabaseHost $databaseHost): array
    {
        $data = $request->validated();

        $nodes = $data['nodes'] ?? [];
        if (count($nodes) > 0) {
            $databaseHost->nodes()->syncWithoutDetaching($nodes);
        }

        return $this->fractal->item($databaseHost)
            ->transformWith($this->getTransformer(DatabaseHostTransformer::class))
            ->toArray();
    }

    /**
     * ?
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Databases\DatabaseNodesRequest $request
     * @param \Pterodactyl\Models\Mount $mount
     *
     * @return array
     */
    public function deleteNodes(DatabaseNodesRequest $request, DatabaseHost $databaseHost): array
    {
        $data = $request->validated();

        $nodes = $data['nodes'] ?? [];
        if (count($nodes) > 0) {
            $databaseHost->nodes()->detach($nodes);
        }

        return $this->fractal->item($databaseHost)
            ->transformWith($this->getTransformer(DatabaseHostTransformer::class))
            ->toArray();
    }
}
