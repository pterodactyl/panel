<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Servers\CreatesServers;
use Pterodactyl\Contracts\Servers\DeletesServers;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Servers\GetServerRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\GetServersRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\StoreServerRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Application\ServerTransformer;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Servers', 'Create, update, retrieve, manage, and delete servers.')]
class ServerController extends ApplicationApiController
{
    private const array DEPLOYMENT_ERROR = [
        'errors' => [
            [
                'code' => 'NoViableNodeException',
                'status' => '400',
                'detail' => 'No nodes satisfying the requirements specified for automatic deployment could be found.',
            ],
        ],
    ];

    /**
     * Return all the servers that currently exist on the Panel.
     *
     * @return ApiPayload
     */
    #[Endpoint('List servers', 'Returns a paginated list of servers visible to the application API key.')]
    #[QueryParam('per_page', 'integer', 'Number of servers to return per page, up to 100.', required: false, example: 50)]
    #[QueryParam('filter[uuid]', 'string', 'Filter servers by UUID.', required: false, example: '4fcb1f44-0f90-4a1a-a8bf-7cc8a14d0f26')]
    #[QueryParam('filter[uuidShort]', 'string', 'Filter servers by short UUID identifier.', required: false, example: '4fcb1f44')]
    #[QueryParam('filter[name]', 'string', 'Filter servers by name.', required: false, example: 'Minecraft Server')]
    #[QueryParam('filter[description]', 'string', 'Filter servers by description.', required: false, example: 'Production')]
    #[QueryParam('filter[image]', 'string', 'Filter servers by Docker image.', required: false, example: 'ghcr.io/pterodactyl/yolks:java_23')]
    #[QueryParam('filter[external_id]', 'string', 'Filter servers by external identifier.', required: false, example: 'billing-server-42')]
    #[QueryParam('search', 'string', 'Search term accepted by the list request. Must not exceed 100 characters.', required: false, example: 'minecraft')]
    #[QueryParam('sort', 'string', 'Sort servers by id or uuid. Prefix with "-" for descending order.', required: false, example: '-id', enum: ['id', '-id', 'uuid', '-uuid'])]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "allocations", "user", "subusers", "egg", "variables", "location", "node", "databases", and "transfer" when the key can read those resources.', required: false, example: 'user,node,allocations')]
    #[ResponseFromTransformer(ServerTransformer::class, Server::class, collection: true, factoryStates: ['withRelationships'], resourceKey: 'server', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetServersRequest $request): array
    {
        $query = Server::query()->with(['egg.variables', 'serverVariables', 'location']);
        $search = $request->validated('search');

        if (is_string($search) && $search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('uuid', 'like', "%{$search}%")
                    ->orWhere('uuidShort', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%");
            });
        }

        $servers = QueryBuilder::for($query)
            ->allowedFilters(['uuid', 'uuidShort', 'name', 'description', 'image', 'external_id'])
            ->allowedSorts(['id', 'uuid'])
            ->paginate($request->perPage());

        return Fractal::collection($servers)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toResponseArray();
    }

    /**
     * Create a new server on the system.
     *
     * @throws Throwable
     * @throws ValidationException
     * @throws DisplayException
     * @throws NoViableAllocationException
     * @throws NoViableNodeException
     */
    #[Endpoint('Create server', 'Creates a new server using either explicit allocations or automatic deployment constraints.')]
    #[ResponseFromTransformer(ServerTransformer::class, Server::class, status: 201, description: 'Server created.', factoryStates: ['withRelationships'], resourceKey: 'server')]
    #[ScribeResponse(self::DEPLOYMENT_ERROR, status: 400, description: 'Automatic deployment could not find a viable node or allocation.')]
    public function store(StoreServerRequest $request, CreatesServers $creation): JsonResponse
    {
        $server = $creation->create($request->payload(), $request->getDeploymentObject());

        return Fractal::item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->respond(201);
    }

    /**
     * Show a single server transformed for the application API.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get server', 'Returns a single server by internal numeric ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "allocations", "user", "subusers", "egg", "variables", "location", "node", "databases", and "transfer" when the key can read those resources.', required: false, example: 'user,node,allocations')]
    #[ResponseFromTransformer(ServerTransformer::class, Server::class, factoryStates: ['withRelationships'], resourceKey: 'server')]
    public function view(GetServerRequest $request, Server $server): array
    {
        return Fractal::item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toResponseArray();
    }

    /**
     * Deletes a server.
     *
     * @throws DisplayException
     */
    #[Endpoint('Delete server', 'Deletes a server. The force route bypasses recoverable Wings or database host deletion failures.')]
    #[ScribeResponse(status: 204, description: 'Server deleted.')]
    public function delete(ServerWriteRequest $request, DeletesServers $deletion, Server $server, string $force = ''): Response
    {
        $deletion->withForce($force === 'force')->delete($server);

        return $this->returnNoContent();
    }
}
