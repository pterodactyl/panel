<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\ServerDatabaseTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class DatabaseController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $repository;

    /**
     * DatabaseController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface $repository
     */
    public function __construct(DatabaseRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Return a listing of all databases currently available to a single
     * server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return array
     */
    public function index(Server $server): array
    {
        $databases = $this->repository->getDatabasesForServer($server->id);

        return $this->fractal->collection($databases)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->toArray();
    }
}
