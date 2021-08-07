<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\CarbonImmutable;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Services\Servers\GetUserPermissionsService;
use Pterodactyl\Http\Requests\Api\Client\WebsocketTokenRequest;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class WebsocketController extends ClientApiController
{
    private NodeJWTService $jwtService;
    private GetUserPermissionsService $permissionsService;

    /**
     * WebsocketController constructor.
     */
    public function __construct(
        NodeJWTService $jwtService,
        GetUserPermissionsService $permissionsService
    ) {
        parent::__construct();

        $this->jwtService = $jwtService;
        $this->permissionsService = $permissionsService;
    }

    /**
     * Generates a one-time token that is sent along in every websocket call to the Daemon.
     * This is a signed JWT that the Daemon then uses the verify the user's identity, and
     * allows us to continually renew this token and avoid users maintaining sessions wrongly,
     * as well as ensure that user's only perform actions they're allowed to.
     */
    public function __invoke(WebsocketTokenRequest $request, Server $server): JsonResponse
    {
        $permissions = $this->permissionsService->handle($server, $request->user());

        $node = $server->node;
        if (!is_null($server->transfer)) {
            // Check if the user has permissions to receive transfer logs.
            if (!in_array('admin.websocket.transfer', $permissions)) {
                throw new HttpForbiddenException('You do not have permission to view server transfer logs.');
            }

            // Redirect the websocket request to the new node if the server has been archived.
            if ($server->transfer->archived) {
                $node = $server->transfer->newNode;
            }
        }

        $token = $this->jwtService
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(10)->toDateTimeImmutable())
            ->setClaims([
                'user_id' => $request->user()->id,
                'server_uuid' => $server->uuid,
                'permissions' => $permissions,
            ])
            ->handle($node, $request->user()->id . $server->uuid);

        $socket = str_replace(['https://', 'http://'], ['wss://', 'ws://'], $node->getConnectionAddress());

        return new JsonResponse([
            'data' => [
                'token' => $token->toString(),
                'socket' => $socket . sprintf('/api/servers/%s/ws', $server->uuid),
            ],
        ]);
    }
}
