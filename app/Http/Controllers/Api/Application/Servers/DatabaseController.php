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
     * @param \Pterodactyl\Models\Server                                                             $server
     * @return array
     */
    public function index(GetServerDatabasesRequest $request, Server $server): array
    {
        $databases = $this->repository->getDatabasesForServer($server->id);

        return $this->fractal->collection($databases)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Return a single server database.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\Databases\GetServerDatabaseRequest $request
     * @param \Pterodactyl\Models\Server                                                            $server
     * @param \Pterodactyl\Models\Database                                                          $database
     * @return array
     */
    public function view(GetServerDatabaseRequest $request, Server $server, Database $database): array
    {
        return $this->fractal->item($database)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Reset the password for a specific server database.
     *
     * @param \Pterodactyl\Models\Server   $server
     * @param \Pterodactyl\Models\Database $database
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function resetPassword(ServerDatabaseWriteRequest $request, Server $server, Database $database): Response
    {
        $this->databasePasswordService->handle($database, str_random(24));

        return response('', 204);
    }

    /**
     * Create a new database on the Panel for a given server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\Databases\StoreServerDatabaseRequest $request
     * @param \Pterodactyl\Models\Server                                                              $server
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(StoreServerDatabaseRequest $request, Server $server): JsonResponse
    {
        $database = $this->databaseManagementService->create($server->id, $request->validated());

        return $this->fractal->item($database)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->respond(201);
    }

    /**
     * Delete a specific database from the Panel.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\Databases\ServerDatabaseWriteRequest $request
     * @param \Pterodactyl\Models\Server                                                              $server
     * @param \Pterodactyl\Models\Database                                                            $database
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(ServerDatabaseWriteRequest $request, Server $server, Database $database): Response
    {
        $this->databaseManagementService->delete($database->id);

        return response('', 204);
    }
}
