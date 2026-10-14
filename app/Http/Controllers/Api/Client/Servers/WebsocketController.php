<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Enum\JwtScope;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Nodes\NodeJwt;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Overview', 'Inspect and control an accessible server.')]
class WebsocketController extends ClientApiController
{
    private const array WEBSOCKET_EXAMPLE = [
        'data' => [
            'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.example.signature',
            'socket' => 'wss://node.example.test/api/servers/4fcb1f44-0f90-4a1a-a8bf-7cc8a14d0f26/ws',
        ],
    ];

    /**
     * Generates a one-time token that is sent along in every websocket call to the Daemon.
     * This is a signed JWT that the Daemon then uses to verify the user's identity, and
     * allows us to continually renew this token and avoid users maintaining sessions wrongly,
     * as well as ensure that user's only perform actions they're allowed to.
     */
    #[Endpoint('Get websocket credentials', 'Returns a short-lived Wings websocket token and socket URL for the server.')]
    #[ScribeResponse(self::WEBSOCKET_EXAMPLE, description: 'Websocket credentials returned.')]
    public function __invoke(ClientApiRequest $request, NodeJwt $jwt, Server $server): JsonResponse
    {
        $user = $request->user();
        throw_if($user->cannot(Permissions::WebsocketConnect->value, $server), HttpForbiddenException::class, "You do not have permission to connect to this server's websocket.");

        $permissions = $user->permissionsOn($server);

        $node = $server->node;
        if (($server->transfer) !== null) {
            // Check if the user has permissions to receive transfer logs.
            throw_unless(in_array('admin.websocket.transfer', $permissions), HttpForbiddenException::class, 'You do not have permission to view server transfer logs.');

            // Redirect the websocket request to the new node if the server has been archived.
            if ($server->transfer->archived) {
                $node = $server->transfer->newNode;
            }
        }

        $token = $jwt->issue(
            $node,
            JwtScope::Websocket,
            identifiedBy: $user->id.$server->uuid,
            expiresAt: CarbonImmutable::now()->addMinutes(10),
            user: $request->user(),
            claims: ['server_uuid' => $server->uuid, 'permissions' => $permissions],
        );

        $socket = str_replace(['https://', 'http://'], ['wss://', 'ws://'], $node->getConnectionAddress());

        return new JsonResponse([
            'data' => [
                'token' => $token->toString(),
                'socket' => $socket.sprintf('/api/servers/%s/ws', $server->uuid),
            ],
        ]);
    }
}
