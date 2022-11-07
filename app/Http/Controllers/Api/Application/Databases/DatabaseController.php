<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Databases;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\DatabaseHost;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Services\Databases\Hosts\HostUpdateService;
use Pterodactyl\Services\Databases\Hosts\HostCreationService;
use Pterodactyl\Transformers\Api\Application\DatabaseHostTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Databases\GetDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Application\Databases\GetDatabasesRequest;
use Pterodactyl\Http\Requests\Api\Application\Databases\StoreDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Application\Databases\DeleteDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Application\Databases\UpdateDatabaseRequest;

class DatabaseController extends ApplicationApiController
{
    /**
     * DatabaseController constructor.
     */
    public function __construct(
        private HostCreationService $creationService,
        private HostUpdateService $updateService
    ) {
        parent::__construct();
    }

    /**
     * Return all the database hosts currently registered on the Panel.
     */
    public function index(GetDatabasesRequest $request): array
    {
        $databases = QueryBuilder::for(DatabaseHost::query())
            ->allowedFilters(['name', 'host'])
            ->allowedSorts(['id', 'name', 'host'])
            ->paginate($request->query('per_page') ?? 10);

        return $this->fractal->collection($databases)
            ->transformWith($this->getTransformer(DatabaseHostTransformer::class))
            ->toArray();
    }

    /**
     * Return a single database host.
     */
    public function view(GetDatabaseRequest $request, DatabaseHost $databaseHost): array
    {
        return $this->fractal->item($databaseHost)
            ->transformWith($this->getTransformer(DatabaseHostTransformer::class))
            ->toArray();
    }

    /**
     * Store a new database host on the Panel and return an HTTP/201 response code with the
     * new database host attached.
     *
     * @throws \Throwable
     */
    public function store(StoreDatabaseRequest $request): JsonResponse
    {
        $databaseHost = $this->creationService->handle($request->validated());

        return $this->fractal->item($databaseHost)
            ->transformWith($this->getTransformer(DatabaseHostTransformer::class))
            ->addMeta([
                'resource' => route('api.application.databases.view', [
                    'databaseHost' => $databaseHost->id,
                ]),
            ])
            ->respond(201);
    }

    /**
     * Update a database host on the Panel and return the updated record to the user.
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
     * Delete a database host from the Panel.
     *
     * @throws \Exception
     */
    public function delete(DeleteDatabaseRequest $request, DatabaseHost $databaseHost): Response
    {
        $databaseHost->delete();

        return $this->returnNoContent();
    }
}
