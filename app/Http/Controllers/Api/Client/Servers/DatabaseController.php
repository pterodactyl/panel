<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Client\DatabaseTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetDatabasesRequest;

class DatabaseController extends ClientApiController
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
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\GetDatabasesRequest $request
     * @return array
     */
    public function index(GetDatabasesRequest $request): array
    {
        $databases = $this->repository->getDatabasesForServer($request->getModel(Server::class)->id);

        return $this->fractal->collection($databases)
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toArray();
    }
}
