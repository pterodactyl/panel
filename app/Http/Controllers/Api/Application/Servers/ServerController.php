<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Extensions\Spatie\Fractalistic\Fractal;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\ServerTransformer;
use Pterodactyl\Http\Requests\Api\Application\Servers\GetServerRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\GetServersRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class ServerController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * ServerController constructor.
     *
     * @param \Pterodactyl\Extensions\Spatie\Fractalistic\Fractal         $fractal
     * @param \Illuminate\Http\Request                                    $request
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(Fractal $fractal, Request $request, ServerRepositoryInterface $repository)
    {
        parent::__construct($fractal, $request);

        $this->repository = $repository;
    }

    /**
     * Return all of the servers that currently exist on the Panel.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\GetServersRequest $request
     * @return array
     */
    public function index(GetServersRequest $request): array
    {
        $servers = $this->repository->setSearchTerm($request->input('search'))->paginated(50);

        return $this->fractal->collection($servers)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }

    /**
     * Show a single server transformed for the application API.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\GetServerRequest $request
     * @param \Pterodactyl\Models\Server                                          $server
     * @return array
     */
    public function view(GetServerRequest $request, Server $server): array
    {
        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }
}
