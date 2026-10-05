<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Requests\Api\Client\GetServersRequest;
use Pterodactyl\Models\Filters\MultiFieldServerFilter;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\SubuserPermissionCatalog;
use Pterodactyl\Transformers\Api\Client\ServerTransformer;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Client Overview', 'Discover servers and permissions available to the authenticated user.')]
class ClientController extends ClientApiController
{
    private const array PERMISSIONS_EXAMPLE = [
        'object' => 'system_permissions',
        'attributes' => [
            'permissions' => [
                'websocket' => [
                    'description' => 'Allows the user to connect to the server websocket, giving them access to view console output and realtime server stats.',
                    'keys' => [
                        'connect' => 'Allows a user to connect to the websocket instance for a server to stream the console.',
                    ],
                ],
                'control' => [
                    'description' => "Permissions that control a user's ability to control the power state of a server, or send commands.",
                    'keys' => [
                        'console' => 'Allows a user to send commands to the server instance via the console.',
                        'start' => 'Allows a user to start the server if it is stopped.',
                        'stop' => 'Allows a user to stop a server if it is running.',
                        'restart' => 'Allows a user to perform a server restart.',
                    ],
                ],
            ],
        ],
    ];

    /**
     * Return all the servers available to the client making the API
     * request, including servers the user has access to as a subuser.
     *
     * @return ApiPayload
     */
    #[Endpoint('List client servers', 'Returns a paginated list of servers the authenticated user can access as an owner, subuser, or administrator.')]
    #[QueryParam('page', 'integer', 'Page number to return.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Number of servers to return per page. The maximum is 100.', required: false, example: 50)]
    #[QueryParam('type', 'string', 'Scope of servers to return. "owner" returns owned servers, "admin" returns admin-accessible servers excluding owned or subuser servers, and "admin-all" returns all servers for root administrators.', required: false, example: 'owner', enum: ['owner', 'admin', 'admin-all'])]
    #[QueryParam('filter[uuid]', 'string', 'Filter servers by UUID.', required: false, example: '4fcb1f44-0f90-4a1a-a8bf-7cc8a14d0f26')]
    #[QueryParam('filter[name]', 'string', 'Filter servers by name.', required: false, example: 'Minecraft Server')]
    #[QueryParam('filter[description]', 'string', 'Filter servers by description.', required: false, example: 'Production')]
    #[QueryParam('filter[external_id]', 'string', 'Filter servers by external identifier.', required: false, example: 'billing-server-42')]
    #[QueryParam('filter[*]', 'string', 'Search across server name, UUID, short UUID, external identifier, or allocation address and port.', required: false, example: '192.0.2.10:25565')]
    #[ResponseFromTransformer(ServerTransformer::class, Server::class, description: 'Accessible servers returned.', collection: true, factoryStates: ['withRelationships'], resourceKey: 'server', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetServersRequest $request): array
    {
        $user = $request->user();
        $transformer = $this->getTransformer(ServerTransformer::class);

        // Start the query builder and ensure we eager load any requested relationships from the request.
        $builder = QueryBuilder::for(
            Server::query()->with($this->getIncludesForTransformer($transformer, ['node']))
        )->allowedFilters([
            'uuid',
            'name',
            'description',
            'external_id',
            AllowedFilter::custom('*', new MultiFieldServerFilter),
        ]);

        $type = $request->validated('type');
        // Either return all the servers the user has access to because they are an admin `?type=admin` or
        // just return all the servers the user has access to because they are the owner or a subuser of the
        // server. If ?type=admin-all is passed all servers on the system will be returned to the user, rather
        // than only servers they can see because they are an admin.
        if (in_array($type, ['admin', 'admin-all'])) {
            // If they aren't an admin but want all the admin servers don't fail the request, just
            // make it a query that will never return any results back.
            if (! $user->root_admin) {
                $builder->whereRaw('1 = 2');
            } else {
                $builder = $type === 'admin-all'
                    ? $builder
                    : $builder->whereNotIn('servers.id', $user->accessibleServers()->pluck('id')->all());
            }
        } elseif ($type === 'owner') {
            $builder = $builder->where('servers.owner_id', $user->id);
        } else {
            $builder = $builder->whereIn('servers.id', $user->accessibleServers()->pluck('id')->all());
        }

        $servers = $builder->paginate(min($request->integer('per_page', 50), 100))->appends($request->query());

        return Fractal::transformWith($transformer)->collection($servers)->toResponseArray();
    }

    /**
     * Returns all the subuser permissions available on the system, including those
     * registered by enabled extensions.
     *
     * @return array{object: string, attributes: array<string, mixed>}
     */
    #[Endpoint('List server permissions', 'Returns the permission keys that can be granted to server subusers, including those registered by enabled extensions under `ext.<extension id>`.')]
    #[ScribeResponse(self::PERMISSIONS_EXAMPLE, description: 'System permission definitions returned.')]
    public function permissions(SubuserPermissionCatalog $catalog): array
    {
        return [
            'object' => 'system_permissions',
            'attributes' => [
                'permissions' => $catalog->groups(),
            ],
        ];
    }
}
