<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Client\StatsTransformer;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest;

class ResourceUtilizationController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $repository;

    /**
     * ResourceUtilizationController constructor.
     */
    public function __construct(DaemonServerRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Return the current resource utilization for a server.
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function __invoke(GetServerRequest $request, Server $server): array
    {
        $stats = $this->repository->setServer($server)->getDetails();

        return $this->fractal->item($stats)
            ->transformWith($this->getTransformer(StatsTransformer::class))
            ->toArray();
    }
}
