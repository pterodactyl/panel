<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\Databases\DatabasePasswordService;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\ServerDatabaseTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Servers\Databases\GetServerDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\Databases\GetServerDatabasesRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\Databases\ServerDatabaseWriteRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\Databases\StoreServerDatabaseRequest;

class DatabaseController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService
     */
    private $databaseManagementService;

    /**
     * @var \Pterodactyl\Services\Databases\DatabasePasswordService
     */
    private $databasePasswordService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $repository;

    /**
     * DatabaseController constructor.
     *
     * @param \Pterodactyl\Services\Databases\DatabaseManagementService     $databaseManagementService
     * @param \Pterodactyl\Services\Databases\DatabasePasswordService       $databasePasswordService
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface $repository
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\Databases\GetServerDatabasesRequest $request
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\Databases\GetServerDatabaseRequest $request
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\Databases\ServerDatabaseWriteRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function resetPassword(ServerDatabaseWriteRequest $request): Response
    {
        $this->databasePasswordService->handle($request->getModel(Database::class), str_random(24));

        return response('', 204);
    }

    /**
     * Create a new database on the Panel for a given server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\Databases\StoreServerDatabaseRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\Databases\ServerDatabaseWriteRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(ServerDatabaseWriteRequest $request): Response
    {
        $this->databaseManagementService->delete($request->getModel(Database::class)->id);

        return response('', 204);
    }
}
