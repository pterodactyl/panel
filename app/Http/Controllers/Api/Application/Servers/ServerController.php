<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Servers\ServerDeletionService;
use Pterodactyl\Services\Servers\BuildModificationService;
use Pterodactyl\Services\Servers\DetailsModificationService;
use Pterodactyl\Transformers\Api\Application\ServerTransformer;
use Pterodactyl\Exceptions\Http\QueryValueOutOfRangeHttpException;
use Pterodactyl\Http\Requests\Api\Application\Servers\GetServerRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\GetServersRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\StoreServerRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Servers\UpdateServerRequest;

class ServerController extends ApplicationApiController
{
    private ServerCreationService $creationService;
    private ServerDeletionService $deletionService;

    private BuildModificationService $buildModificationService;
    private DetailsModificationService $detailsModificationService;

    /**
     * ServerController constructor.
     */
    public function __construct(
        ServerCreationService $creationService,
        ServerDeletionService $deletionService,
        BuildModificationService $buildModificationService,
        DetailsModificationService $detailsModificationService
    ) {
        parent::__construct();

        $this->creationService = $creationService;
        $this->deletionService = $deletionService;

        $this->buildModificationService = $buildModificationService;
        $this->detailsModificationService = $detailsModificationService;
    }

    /**
     * Return all of the servers that currently exist on the Panel.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetServersRequest $request): array
    {
        $perPage = $request->query('per_page', 10);
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $servers = QueryBuilder::for(Server::query())
            ->allowedFilters(['id', 'uuid', 'uuidShort', 'name', 'owner_id', 'node_id', 'external_id'])
            ->allowedSorts(['id', 'uuid', 'uuidShort', 'name', 'owner_id', 'node_id', 'status'])
            ->paginate($perPage);

        return $this->fractal->collection($servers)
            ->transformWith(ServerTransformer::class)
            ->toArray();
    }

    /**
     * Create a new server on the system.
     *
     * @throws \Throwable
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException
     */
    public function store(StoreServerRequest $request): JsonResponse
    {
        $server = $this->creationService->handle($request->validated(), $request->getDeploymentObject());

        return $this->fractal->item($server)
            ->transformWith(ServerTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Show a single server transformed for the application API.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(GetServerRequest $request, Server $server): array
    {
        return $this->fractal->item($server)
            ->transformWith(ServerTransformer::class)
            ->toArray();
    }

    /**
     * Deletes a server.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Throwable
     */
    public function delete(ServerWriteRequest $request, Server $server, string $force = ''): Response
    {
        $this->deletionService->withForce($force === 'force')->handle($server);

        return $this->returnNoContent();
    }

    /**
     * Update a server.
     *
     * @throws \Throwable
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException
     */
    public function update(UpdateServerRequest $request, Server $server): array
    {
        $server = $this->buildModificationService->handle($server, $request->validated());
        $server = $this->detailsModificationService->returnUpdatedModel()->handle($server, $request->validated());

        return $this->fractal->item($server)
            ->transformWith(ServerTransformer::class)
            ->toArray();
    }
}
