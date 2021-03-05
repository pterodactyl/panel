<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Databases;

use Illuminate\Http\Response;
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
use Pterodactyl\Http\Requests\Api\Application\Databases\StoreDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Application\Databases\DeleteDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Application\Databases\UpdateDatabaseRequest;

class DatabaseController extends ApplicationApiController
{
    private HostCreationService $creationService;
    private HostUpdateService $updateService;

    /**
     * DatabaseController constructor.
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
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetDatabasesRequest $request): array
    {
        $perPage = $request->query('per_page', 10);
        if ($perPage < 1) {
            $perPage = 10;
        } elseif ($perPage > 100) {
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
     * @throws \Exception
     */
    public function delete(DeleteDatabaseRequest $request, DatabaseHost $databaseHost): Response
    {
        $databaseHost->delete();

        return $this->returnNoContent();
    }
}
