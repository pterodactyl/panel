<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\GetUserPermissionsService;
use Pterodactyl\Transformers\Api\Client\ServerTransformer;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Overview', 'Inspect and control an accessible server.')]
class ServerController extends ClientApiController
{
    /**
     * Transform an individual server into a response that can be consumed by a
     * client using the API.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get server', 'Returns details for one server the authenticated user can access.')]
    #[ResponseFromTransformer(ServerTransformer::class, Server::class, description: 'Server details returned.', factoryStates: ['withRelationships'], resourceKey: 'server', meta: ['is_server_owner' => true, 'user_permissions' => ['websocket.connect', 'control.console', 'control.start', 'control.stop', 'control.restart']])]
    public function index(GetServerRequest $request, GetUserPermissionsService $permissionsService, Server $server): array
    {
        return Fractal::item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->addMeta([
                'is_server_owner' => $request->user()->id === $server->owner_id,
                'user_permissions' => $permissionsService->handle($server, $request->user()),
            ])
            ->toResponseArray();
    }
}
