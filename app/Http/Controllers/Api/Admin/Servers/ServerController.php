<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Servers\CreatesServers;
use Pterodactyl\Contracts\Servers\DeletesServers;
use Pterodactyl\Extensions\Scribe\Attributes\ExtensionFieldsParam;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\DeleteServerRequest;
use Pterodactyl\Http\Requests\Api\Admin\Servers\GetServerRequest;
use Pterodactyl\Http\Requests\Api\Admin\Servers\GetServersRequest;
use Pterodactyl\Http\Requests\Api\Admin\Servers\StoreServerRequest;
use Pterodactyl\Models\Filters\AdminServerFilter;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Extensions\ExtensionFormFields;
use Pterodactyl\Transformers\Api\Admin\ServerTransformer;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class ServerController extends AdminApiController
{
    public const array SERVER_EXAMPLE = [
        'object' => 'server',
        'attributes' => [
            'id' => 1,
            'external_id' => 'remote-123',
            'uuid' => '1b19cf3f-2f89-4f88-a81e-321e7fe326bc',
            'identifier' => '1b19cf3f',
            'name' => 'Survival',
            'description' => 'Minecraft survival server',
            'status' => null,
            'suspended' => false,
            'limits' => [
                'memory' => 2048,
                'swap' => 0,
                'disk' => 10240,
                'io' => 500,
                'cpu' => 100,
                'threads' => null,
                'oom_disabled' => false,
            ],
            'feature_limits' => [
                'databases' => 1,
                'allocations' => 1,
                'backups' => 1,
            ],
            'user' => 1,
            'node' => 1,
            'allocation' => 1,
            'egg' => 1,
            'container' => [
                'startup_command' => 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar server.jar',
                'image' => 'ghcr.io/pterodactyl/yolks:java_21',
                'skip_scripts' => false,
                'installed' => 1,
                'environment' => [
                    'SERVER_JARFILE' => 'server.jar',
                ],
            ],
            'updated_at' => '2026-01-01T00:00:00+00:00',
            'created_at' => '2026-01-01T00:00:00+00:00',
            'relationships' => [
                'allocation' => [
                    'object' => 'allocation',
                    'attributes' => [
                        'id' => 1,
                        'ip' => '192.0.2.10',
                        'alias' => null,
                        'port' => 25565,
                        'notes' => null,
                        'assigned' => true,
                    ],
                ],
                'allocations' => [
                    'object' => 'list',
                    'data' => [
                        [
                            'object' => 'allocation',
                            'attributes' => [
                                'id' => 1,
                                'ip' => '192.0.2.10',
                                'alias' => null,
                                'port' => 25565,
                                'notes' => null,
                                'assigned' => true,
                            ],
                        ],
                    ],
                ],
                'user' => [
                    'object' => 'user',
                    'attributes' => [
                        'id' => 1,
                        'external_id' => null,
                        'uuid' => '4de5a357-ed3f-4d7b-bc2c-664b74cd714d',
                        'username' => 'admin',
                        'email' => 'admin@example.com',
                        'first_name' => 'Admin',
                        'last_name' => 'User',
                        'language' => 'en',
                        'root_admin' => true,
                        '2fa' => true,
                        'image' => 'https://www.gravatar.com/avatar/example',
                        'servers_count' => 1,
                        'subuser_of_count' => 0,
                        'created_at' => '2026-01-01T00:00:00+00:00',
                        'updated_at' => '2026-01-01T00:00:00+00:00',
                    ],
                ],
                'node' => [
                    'object' => 'node',
                    'attributes' => [
                        'id' => 1,
                        'uuid' => '1b19cf3f-2f89-4f88-a81e-321e7fe326bc',
                        'public' => true,
                        'name' => 'Node 1',
                        'description' => 'Primary node',
                        'location_id' => 1,
                        'fqdn' => 'node.example.com',
                        'scheme' => 'https',
                        'behind_proxy' => false,
                        'maintenance_mode' => false,
                        'memory' => 32768,
                        'memory_overallocate' => 0,
                        'disk' => 524288,
                        'disk_overallocate' => 0,
                        'upload_size' => 100,
                        'daemon_listen' => 8080,
                        'daemon_sftp' => 2022,
                        'daemon_base' => '/var/lib/pterodactyl/volumes',
                        'created_at' => '2026-01-01T00:00:00+00:00',
                        'updated_at' => '2026-01-01T00:00:00+00:00',
                    ],
                ],
                'egg' => [
                    'object' => 'egg',
                    'attributes' => [
                        'id' => 1,
                        'uuid' => '2a2dc402-ccaa-4f2d-b7bb-46b97b473c39',
                        'author' => 'support@pterodactyl.io',
                        'name' => 'Minecraft Java',
                        'description' => 'Minecraft Java server',
                        'features' => [],
                        'docker_image' => 'ghcr.io/pterodactyl/yolks:java_21',
                        'docker_images' => [
                            'Java 21' => 'ghcr.io/pterodactyl/yolks:java_21',
                        ],
                        'force_outgoing_ip' => false,
                        'config' => [
                            'files' => [],
                            'startup' => ['done' => ['Done']],
                            'stop' => 'stop',
                            'logs' => ['custom' => false, 'location' => 'logs/latest.log'],
                            'extends' => null,
                        ],
                        'startup' => 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}',
                        'script' => [
                            'privileged' => false,
                            'install' => '#!/bin/ash',
                            'entry' => 'ash',
                            'container' => 'ghcr.io/pterodactyl/installers:alpine',
                            'extends' => null,
                        ],
                        'created_at' => '2026-01-01T00:00:00+00:00',
                        'updated_at' => '2026-01-01T00:00:00+00:00',
                    ],
                ],
                'variables' => [
                    'object' => 'list',
                    'data' => [
                        [
                            'object' => 'server_variable',
                            'attributes' => [
                                'id' => 1,
                                'egg_id' => 1,
                                'name' => 'Server Jar File',
                                'description' => 'The jar file to run.',
                                'env_variable' => 'SERVER_JARFILE',
                                'default_value' => 'server.jar',
                                'server_value' => 'server.jar',
                                'user_viewable' => true,
                                'user_editable' => true,
                                'rules' => 'required|string',
                                'required' => true,
                                'sort_order' => 1,
                                'created_at' => '2026-01-01T00:00:00+00:00',
                                'updated_at' => '2026-01-01T00:00:00+00:00',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * List servers.
     *
     * @return ApiPayload
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
    #[ResponseFromTransformer(ServerTransformer::class, Server::class, description: 'Servers returned.', collection: true, factoryStates: ['withRelationships'], resourceKey: 'server', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetServersRequest $request): array
    {
        $servers = QueryBuilder::for(Server::query()->with('allocation', 'node', 'user'))
            ->allowedFilters([
                'uuid',
                'uuidShort',
                'name',
                'external_id',
                'image',
                'node_id',
                AllowedFilter::exact('owner_id'),
                AllowedFilter::custom('*', new AdminServerFilter),
            ])
            ->allowedSorts(['id', 'uuid', 'name', 'created_at'])
            ->paginate($request->perPage());

        return Fractal::collection($servers)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toResponseArray();
    }

    /**
     * Show server.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get server', 'Returns a single server by admin identifier.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports allocation, allocations, user, subusers, egg, variables, location, node, and databases.', required: false, example: 'allocations,user,node,egg,variables')]
    #[ResponseFromTransformer(ServerTransformer::class, Server::class, description: 'Server returned.', factoryStates: ['withRelationships'], resourceKey: 'server', include: ['allocation', 'allocations', 'egg', 'node', 'user', 'variables'])]
    public function show(GetServerRequest $request, Server $server): array
    {
        return Fractal::item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toResponseArray();
    }

    /**
     * Create server.
     */
    #[Endpoint('Create server', 'Creates a server using explicit allocations or automatic deployment constraints.')]
    #[ResponseFromTransformer(ServerTransformer::class, Server::class, status: 201, description: 'Server created.', factoryStates: ['withRelationships'], resourceKey: 'server', meta: ['resource' => 'https://panel.example.com/api/admin/servers/1'])]
    #[ExtensionFieldsParam]
    public function store(StoreServerRequest $request, CreatesServers $creation, ExtensionFormFields $fields): JsonResponse
    {
        $server = $fields->persist('admin.server', $request->extensionFields(), fn (): Server => $creation->create($request->payload(), $request->getDeploymentObject()), atomic: false);

        Activity::event('admin:server.create')
            ->subject($server)
            ->property('name', $server->name)
            ->log();

        return Fractal::item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->addMeta([
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
