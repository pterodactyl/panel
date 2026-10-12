<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest;
use Pterodactyl\Http\Resources\Api\Client\ServerResource;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\GetUserPermissionsService;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Overview', 'Inspect and control an accessible server.')]
class ServerController extends ClientApiController
{
    /**
     * Transform an individual server into a response that can be consumed by a
     * client using the API.
     */
    #[Endpoint('Get server', 'Returns details for one server the authenticated user can access.')]
    #[ResponseFromResource(ServerResource::class, Server::class, description: 'Server details returned.', factoryStates: ['withRelationships'], resourceKey: 'server', meta: ['is_server_owner' => true, 'user_permissions' => ['websocket.connect', 'control.console', 'control.start', 'control.stop', 'control.restart']])]
    public function index(GetServerRequest $request, GetUserPermissionsService $permissionsService, Server $server): ServerResource
    {
        return ServerResource::make($server)->withMeta([
            'is_server_owner' => $request->user()->id === $server->owner_id,
            'user_permissions' => $permissionsService->handle($server, $request->user()),
        ]);
    }
}
