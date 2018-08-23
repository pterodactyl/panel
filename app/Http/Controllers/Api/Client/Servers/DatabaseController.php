<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Client\DatabaseTransformer;
use Pterodactyl\Services\Databases\DeployServerDatabaseService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\GetDatabasesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\StoreDatabaseRequest;

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
     * DatabaseController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface $repository
     * @param \Pterodactyl\Services\Databases\DeployServerDatabaseService   $deployDatabaseService
     */
    public function __construct(DatabaseRepositoryInterface $repository, DeployServerDatabaseService $deployDatabaseService)
    {
        parent::__construct();

        $this->deployDatabaseService = $deployDatabaseService;
        $this->repository = $repository;
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
}
