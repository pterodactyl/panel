<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\ServerDeletionService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\ServerTransformer;
use Pterodactyl\Http\Requests\Api\Application\Servers\GetServersRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class ServerController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Services\Servers\ServerDeletionService
     */
    private $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * ServerController constructor.
     *
     * @param \Pterodactyl\Services\Servers\ServerDeletionService         $deletionService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(ServerDeletionService $deletionService, ServerRepositoryInterface $repository)
    {
        parent::__construct();

        $this->deletionService = $deletionService;
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @param \Pterodactyl\Models\Server                                            $server
     * @return array
     */
    public function view(ServerWriteRequest $request, Server $server): array
    {
        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }

    /**
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @param \Pterodactyl\Models\Server                                            $server
     * @param string                                                                $force
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(ServerWriteRequest $request, Server $server, string $force = ''): Response
    {
        $this->deletionService->withForce($force === 'force')->handle($server);

        return $this->returnNoContent();
    }
}
