<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Services\Databases\DatabasePasswordService;
use Pterodactyl\Transformers\Api\Client\DatabaseTransformer;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Services\Databases\DeployServerDatabaseService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
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
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
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
     * @param \Pterodactyl\Services\Databases\DatabaseManagementService     $managementService
     * @param \Pterodactyl\Services\Databases\DatabasePasswordService       $passwordService
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface $repository
     * @param \Pterodactyl\Services\Databases\DeployServerDatabaseService   $deployDatabaseService
     */
    public function __construct(
        DatabaseManagementService $managementService,
        DatabasePasswordService $passwordService,
        DatabaseRepositoryInterface $repository,
        DeployServerDatabaseService $deployDatabaseService
    ) {
        parent::__construct();

        $this->deployDatabaseService = $deployDatabaseService;
        $this->repository = $repository;
        $this->managementService = $managementService;
        $this->passwordService = $passwordService;
    }

    /**
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Databases\GetDatabasesRequest $request
     * @return array
     */
    public function index(GetDatabasesRequest $request): array
    {
        $databases = $this->repository->getDatabasesForServer($request->getModel(Server::class)->id);

        return $this->fractal->collection($databases)
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Create a new database for the given server and return it.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Databases\StoreDatabaseRequest $request
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException
     */
    public function store(StoreDatabaseRequest $request): array
    {
        $database = $this->deployDatabaseService->handle($request->getModel(Server::class), $request->validated());

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
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function rotatePassword(RotatePasswordRequest $request)
    {
        $database = $request->getModel(Database::class);

        $this->passwordService->handle($database, Str::random(24));

        $database->refresh();

        return $this->fractal->item($database)
            ->parseIncludes(['password'])
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toArray();
    }

    /**
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Databases\DeleteDatabaseRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(DeleteDatabaseRequest $request): Response
    {
        $this->managementService->delete($request->getModel(Database::class)->id);

        return Response::create('', Response::HTTP_NO_CONTENT);
    }
}
