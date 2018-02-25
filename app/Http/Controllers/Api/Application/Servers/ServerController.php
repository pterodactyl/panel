<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Servers\ServerDeletionService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\ServerTransformer;
use Pterodactyl\Http\Requests\Api\Application\Servers\GetServerRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\GetServersRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\StoreServerRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class ServerController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Services\Servers\ServerCreationService
     */
    private $creationService;

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
     * @param \Pterodactyl\Services\Servers\ServerCreationService         $creationService
     * @param \Pterodactyl\Services\Servers\ServerDeletionService         $deletionService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(
        ServerCreationService $creationService,
        ServerDeletionService $deletionService,
        ServerRepositoryInterface $repository
    ) {
        parent::__construct();

        $this->creationService = $creationService;
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
     * Create a new server on the system.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\StoreServerRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException
     */
    public function store(StoreServerRequest $request): JsonResponse
    {
        $server = $this->creationService->handle($request->validated(), $request->getDeploymentObject());

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->respond(201);
    }

    /**
     * Show a single server transformed for the application API.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\GetServerRequest $request
     * @return array
     */
    public function view(GetServerRequest $request): array
    {
        return $this->fractal->item($request->getModel(Server::class))
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
     */
    public function delete(ServerWriteRequest $request, Server $server, string $force = ''): Response
    {
        $this->deletionService->withForce($force === 'force')->handle($server);

        return $this->returnNoContent();
    }
}
