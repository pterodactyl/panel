<?php

namespace App\Http\Controllers\Api\Application\Servers;

use App\Models\Server;
use App\Models\Database;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Services\Databases\DatabasePasswordService;
use App\Services\Databases\DatabaseManagementService;
use App\Contracts\Repository\DatabaseRepositoryInterface;
use App\Transformers\Api\Application\ServerDatabaseTransformer;
use App\Http\Controllers\Api\Application\ApplicationApiController;
use App\Http\Requests\Api\Application\Servers\Databases\GetServerDatabaseRequest;
use App\Http\Requests\Api\Application\Servers\Databases\GetServerDatabasesRequest;
use App\Http\Requests\Api\Application\Servers\Databases\ServerDatabaseWriteRequest;
use App\Http\Requests\Api\Application\Servers\Databases\StoreServerDatabaseRequest;

class DatabaseController extends ApplicationApiController
{
    /**
     * @var \App\Services\Databases\DatabaseManagementService
     */
    private $databaseManagementService;

    /**
     * @var \App\Services\Databases\DatabasePasswordService
     */
    private $databasePasswordService;

    /**
     * @var \App\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $repository;

    /**
     * DatabaseController constructor.
     *
     * @param \App\Services\Databases\DatabaseManagementService     $databaseManagementService
     * @param \App\Services\Databases\DatabasePasswordService       $databasePasswordService
     * @param \App\Contracts\Repository\DatabaseRepositoryInterface $repository
     */
    public function __construct(
        DatabaseManagementService $databaseManagementService,
        DatabasePasswordService $databasePasswordService,
        DatabaseRepositoryInterface $repository
    ) {
        parent::__construct();

        $this->databaseManagementService = $databaseManagementService;
        $this->databasePasswordService = $databasePasswordService;
        $this->repository = $repository;
    }

    /**
     * Return a listing of all databases currently available to a single
     * server.
     *
     * @param \App\Http\Requests\Api\Application\Servers\Databases\GetServerDatabasesRequest $request
     * @return array
     */
    public function index(GetServerDatabasesRequest $request): array
    {
        $databases = $this->repository->getDatabasesForServer($request->getModel(Server::class)->id);

        return $this->fractal->collection($databases)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Return a single server database.
     *
     * @param \App\Http\Requests\Api\Application\Servers\Databases\GetServerDatabaseRequest $request
     * @return array
     */
    public function view(GetServerDatabaseRequest $request): array
    {
        return $this->fractal->item($request->getModel(Database::class))
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Reset the password for a specific server database.
     *
     * @param \App\Http\Requests\Api\Application\Servers\Databases\ServerDatabaseWriteRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
    public function resetPassword(ServerDatabaseWriteRequest $request): Response
    {
        $this->databasePasswordService->handle($request->getModel(Database::class));

        return response('', 204);
    }

    /**
     * Create a new database on the Panel for a given server.
     *
     * @param \App\Http\Requests\Api\Application\Servers\Databases\StoreServerDatabaseRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function store(StoreServerDatabaseRequest $request): JsonResponse
    {
        $server = $request->getModel(Server::class);
        $database = $this->databaseManagementService->create($server->id, $request->validated());

        return $this->fractal->item($database)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->addMeta([
                'resource' => route('api.application.servers.databases.view', [
                    'server' => $server->id,
                    'database' => $database->id,
                ]),
            ])
            ->respond(201);
    }

    /**
     * Handle a request to delete a specific server database from the Panel.
     *
     * @param \App\Http\Requests\Api\Application\Servers\Databases\ServerDatabaseWriteRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(ServerDatabaseWriteRequest $request): Response
    {
        $this->databaseManagementService->delete($request->getModel(Database::class)->id);

        return response('', 204);
    }
}
