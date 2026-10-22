<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Client\ServerTransformer;
use Pterodactyl\Services\Servers\GetUserPermissionsService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest;

class ServerController extends ClientApiController
{
    /**
     * ServerController constructor.
     */
    public function __construct(private GetUserPermissionsService $permissionsService)
    {
        parent::__construct();
    }

    /**
     * Transform an individual server into a response that can be consumed by a
     * client using the API.
     */
    public function index(GetServerRequest $request, Server $server): array
    {
        $permissions = $this->permissionsService->handle($server, $request->user());

        // When the request is authenticated with a permission-scoped API key, the
        // reported permission set is clamped to the key's scope so the client is
        // not told it can perform actions the key will actually be denied.
        $token = $request->user()->currentApiKey();
        if (!is_null($token) && !is_null($token->permissions)) {
            $permissions = in_array('*', $permissions, true)
                ? $token->permissions
                : array_values(array_intersect($permissions, $token->permissions));
        }

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->addMeta([
                'is_server_owner' => $request->user()->id === $server->owner_id,
                'user_permissions' => $permissions,
            ])
            ->toArray();
    }
}
