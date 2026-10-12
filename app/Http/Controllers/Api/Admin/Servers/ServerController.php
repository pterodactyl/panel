<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\CreatesServers;
use Pterodactyl\Contracts\Servers\DeletesServers;
use Pterodactyl\Extensions\Scribe\Attributes\ExtensionFieldsParam;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Extensions\Scribe\Support\PterodactylDocumentation;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\DeleteServerRequest;
use Pterodactyl\Http\Requests\Api\Admin\Servers\GetServerRequest;
use Pterodactyl\Http\Requests\Api\Admin\Servers\GetServersRequest;
use Pterodactyl\Http\Requests\Api\Admin\Servers\StoreServerRequest;
use Pterodactyl\Http\Resources\Api\Admin\ServerResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Models\Filters\AdminServerFilter;
use Pterodactyl\Models\Server;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class ServerController extends AdminApiController
{
    /**
     * List servers.
     */
    #[Endpoint('List servers', 'Returns a paginated list of servers.')]
    #[QueryParam('filter[*]', 'string', 'Search servers by UUID, name, owner, node, allocation, or external identifier.', required: false, example: 'Survival')]
    #[QueryParam('filter[uuid]', 'string', 'Filter servers by UUID.', required: false, example: '1b19cf3f-2f89-4f88-a81e-321e7fe326bc')]
    #[QueryParam('filter[uuidShort]', 'string', 'Filter servers by short UUID identifier.', required: false, example: '1b19cf3f')]
    #[QueryParam('filter[name]', 'string', 'Filter servers by name.', required: false, example: 'Survival')]
    #[QueryParam('filter[external_id]', 'string', 'Filter servers by external identifier.', required: false, example: 'remote-123')]
    #[QueryParam('filter[image]', 'string', 'Filter servers by Docker image.', required: false, example: 'ghcr.io/pterodactyl/yolks:java_21')]
    #[QueryParam('filter[node_id]', 'integer', 'Filter servers by node ID.', required: false, example: 1)]
    #[QueryParam('filter[owner_id]', 'integer', 'Filter servers by owner user ID.', required: false, example: 1)]
    #[QueryParam('sort', 'string', 'Sort servers by id, uuid, name, or creation time. Prefix with a hyphen for descending order.', required: false, example: '-created_at')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports allocation, allocations, user, subusers, egg, variables, location, node, and databases.', required: false, example: 'user,node,allocation')]
    #[QueryParam('page', 'integer', 'Page number to retrieve.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', required: false, example: 50)]
    #[ResponseFromResource(ServerResource::class, Server::class, description: 'Servers returned.', collection: true, factoryStates: ['withRelationships'], resourceKey: 'server', paginate: 50)]
    public function index(GetServersRequest $request): ApiResourceCollection
    {
        $servers = QueryBuilder::for(Server::query()->with('allocation', 'node', 'user'))
            ->allowedFilters([
                'uuid',
                'uuidShort',
                'name',
                'external_id',
                'image',
                AllowedFilter::exact('node_id'),
                AllowedFilter::exact('owner_id'),
                AllowedFilter::custom('*', new AdminServerFilter),
            ])
            ->allowedSorts(['id', 'uuid', 'name', 'created_at'])
            ->paginate($request->perPage());

        return ServerResource::collection($servers);
    }

    /**
     * Show server.
     */
    #[Endpoint('Get server', 'Returns a single server by admin identifier.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports allocation, allocations, user, subusers, egg, variables, location, node, and databases.', required: false, example: 'allocations,user,node,egg,variables')]
    #[ResponseFromResource(ServerResource::class, Server::class, description: 'Server returned.', factoryStates: ['withRelationships'], resourceKey: 'server', include: ['allocation', 'allocations', 'egg', 'node', 'user', 'variables'])]
    public function show(GetServerRequest $request, Server $server): ServerResource
    {
        return ServerResource::make($server)->withExtensionFields();
    }

    /**
     * Create server.
     */
    #[Endpoint('Create server', 'Creates a server using explicit allocations or automatic deployment constraints.')]
    #[BodyParam('deploy.tags', 'string[]', PterodactylDocumentation::DEPLOY_TAGS, required: false, example: ['premium'])]
    #[ResponseFromResource(ServerResource::class, Server::class, status: 201, description: 'Server created.', factoryStates: ['withRelationships'], resourceKey: 'server', meta: ['resource' => 'https://panel.example.com/api/admin/servers/1'])]
    #[ExtensionFieldsParam]
    public function store(StoreServerRequest $request, CreatesServers $creation): JsonResponse
    {
        $server = $creation->create($request->actionInput(), $request->getDeploymentObject());

        Activity::event('admin:server.create')
            ->subject($server)
            ->property('name', $server->name)
            ->log();

        return ServerResource::make($server)
            ->withExtensionFields()
            ->withMeta([
                'resource' => route('api.admin.servers.view', [
                    'server' => $server->id,
                ]),
            ])
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Delete server.
     */
    #[Endpoint('Delete server', 'Deletes a server and returns an error if Wings or database host cleanup fails.')]
    #[ScribeResponse(status: 204, description: 'Server deleted.')]
    public function destroy(DeleteServerRequest $request, DeletesServers $deletion, Server $server, string $force = ''): Response
    {
        $deletion->withForce($force === 'force')->delete($server);

        Activity::event('admin:server.delete')
            ->subject($server)
            ->property('name', $server->name)
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Force delete server.
     */
    #[Endpoint('Force delete server', 'Deletes a server while bypassing recoverable Wings or database host cleanup failures.')]
    #[ScribeResponse(status: 204, description: 'Server force deleted.')]
    public function forceDestroy(DeleteServerRequest $request, DeletesServers $deletion, Server $server): Response
    {
        return $this->destroy($request, $deletion, $server, 'force');
    }
}
