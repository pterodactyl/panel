<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Repositories\Eloquent\DatabaseRepository;
use Pterodactyl\Services\Databases\DatabasePasswordService;
use Pterodactyl\Transformers\Api\Client\DatabaseTransformer;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Services\Databases\DeployServerDatabaseService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\GetDatabasesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\StoreDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\DeleteDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\RotatePasswordRequest;

class DatabaseController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Services\Databases\DeployServerDatabaseService
     */
    private $deployDatabaseService;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\DatabaseRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService
     */
    private $managementService;

    /**
     * @var \Pterodactyl\Services\Databases\DatabasePasswordService
     */
    private $passwordService;

    /**
     * DatabaseController constructor.
     *
     * @param \Pterodactyl\Services\Databases\DatabaseManagementService $managementService
     * @param \Pterodactyl\Services\Databases\DatabasePasswordService $passwordService
     * @param \Pterodactyl\Repositories\Eloquent\DatabaseRepository $repository
     * @param \Pterodactyl\Services\Databases\DeployServerDatabaseService $deployDatabaseService
     */
    public function __construct(
        DatabaseManagementService $managementService,
        DatabasePasswordService $passwordService,
        DatabaseRepository $repository,
        DeployServerDatabaseService $deployDatabaseService
    ) {
        parent::__construct();

        $this->deployDatabaseService = $deployDatabaseService;
        $this->repository = $repository;
        $this->managementService = $managementService;
        $this->passwordService = $passwordService;
    }

    /**
     * Return all of the databases that belong to the given server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Databases\GetDatabasesRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     */
    public function index(GetDatabasesRequest $request, Server $server): array
    {
        return $this->fractal->collection($server->databases)
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Create a new database for the given server and return it.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Databases\StoreDatabaseRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Throwable
     * @throws \Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException
     * @throws \Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException
     */
    public function store(StoreDatabaseRequest $request, Server $server): array
    {
        $database = $this->deployDatabaseService->handle($server, $request->validated());

        return $this->fractal->item($database)
            ->parseIncludes(['password'])
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Rotates the password for the given server model and returns a fresh instance to
     * the caller.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Databases\RotatePasswordRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Database $database
     * @return array
     *
     * @throws \Throwable
     */
    public function rotatePassword(RotatePasswordRequest $request, Server $server, Database $database)
    {
        $this->passwordService->handle($database);
        $database->refresh();

        return $this->fractal->item($database)
            ->parseIncludes(['password'])
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Removes a database from the server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Databases\DeleteDatabaseRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Database $database
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(DeleteDatabaseRequest $request, Server $server, Database $database): Response
    {
        $this->managementService->delete($database);

        return Response::create('', Response::HTTP_NO_CONTENT);
    }
}
