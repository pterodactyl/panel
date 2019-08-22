<?php

namespace App\Http\Controllers\Api\Application\Servers;

use App\Models\Server;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Services\Servers\ServerCreationService;
use App\Services\Servers\ServerDeletionService;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Transformers\Api\Application\ServerTransformer;
use App\Http\Requests\Api\Application\Servers\GetServerRequest;
use App\Http\Requests\Api\Application\Servers\GetServersRequest;
use App\Http\Requests\Api\Application\Servers\ServerWriteRequest;
use App\Http\Requests\Api\Application\Servers\StoreServerRequest;
use App\Http\Controllers\Api\Application\ApplicationApiController;

class ServerController extends ApplicationApiController
{
    /**
     * @var \App\Services\Servers\ServerCreationService
     */
    private $creationService;

    /**
     * @var \App\Services\Servers\ServerDeletionService
     */
    private $deletionService;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * ServerController constructor.
     *
     * @param \App\Services\Servers\ServerCreationService         $creationService
     * @param \App\Services\Servers\ServerDeletionService         $deletionService
     * @param \App\Contracts\Repository\ServerRepositoryInterface $repository
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
     * @param \App\Http\Requests\Api\Application\Servers\GetServersRequest $request
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
     * @param \App\Http\Requests\Api\Application\Servers\StoreServerRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Deployment\NoViableAllocationException
     * @throws \App\Exceptions\Service\Deployment\NoViableNodeException
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
     * @param \App\Http\Requests\Api\Application\Servers\GetServerRequest $request
     * @return array
     */
    public function view(GetServerRequest $request): array
    {
        return $this->fractal->item($request->getModel(Server::class))
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }

    /**
     * @param \App\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @param \App\Models\Server                                            $server
     * @param string                                                                $force
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\DisplayException
     */
    public function delete(ServerWriteRequest $request, Server $server, string $force = ''): Response
    {
        $this->deletionService->withForce($force === 'force')->handle($server);

        return $this->returnNoContent();
    }
}
