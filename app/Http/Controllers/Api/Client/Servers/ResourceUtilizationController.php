<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\WingsServerRepository;
use Pterodactyl\Transformers\Api\Client\StatsTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest;

class ResourceUtilizationController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Wings\WingsServerRepository
     */
    private $repository;

    /**
     * ResourceUtilizationController constructor.
     *
     * @param \Pterodactyl\Repositories\Wings\WingsServerRepository $repository
     */
    public function __construct(WingsServerRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Return the current resource utilization for a server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest $request
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function __invoke(GetServerRequest $request): array
    {
        $stats = $this->repository
            ->setServer($request->getModel(Server::class))
            ->getDetails();

        return $this->fractal->item($stats)
            ->transformWith($this->getTransformer(StatsTransformer::class))
            ->toArray();
    }
}
