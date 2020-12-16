<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Permission;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Services\Servers\GetUserPermissionsService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class WebsocketController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Services\Nodes\NodeJWTService
     */
    private $jwtService;

    /**
     * @var \Pterodactyl\Services\Servers\GetUserPermissionsService
     */
    private $permissionsService;

    /**
     * WebsocketController constructor.
     *
     * @param \Pterodactyl\Services\Nodes\NodeJWTService $jwtService
     * @param \Pterodactyl\Services\Servers\GetUserPermissionsService $permissionsService
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
     * allows us to continually renew this token and avoid users mainitaining sessions wrongly,
     * as well as ensure that user's only perform actions they're allowed to.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\ClientApiRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ClientApiRequest $request, Server $server)
    {
        $user = $request->user();
        if ($user->cannot(Permission::ACTION_WEBSOCKET_CONNECT, $server)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'You do not have permission to connect to this server\'s websocket.');
        }

        $permissions = $this->permissionsService->handle($server, $user);

        $node = null;

        // Check if there is a transfer query param asking to connect to the target node's websocket.
        if ($request->query('transfer', 'false') === 'true') {
            // Check if the user has permissions to receive transfer logs.
            if (! in_array('admin.websocket.transfer', $permissions)) {
                throw new HttpException(Response::HTTP_FORBIDDEN, 'You do not have permission to get transfer logs');
            }

            $node = $server->transfer->newNode;
        } else {
            $node = $server->node;
        }

        $token = $this->jwtService
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(10))
            ->setClaims([
                'user_id' => $request->user()->id,
                'server_uuid' => $server->uuid,
                'permissions' => $permissions,
            ])
            ->handle($node, $user->id . $server->uuid);

        $socket = str_replace(['https://', 'http://'], ['wss://', 'ws://'], $node->getConnectionAddress());

        return new JsonResponse([
            'data' => [
                'token' => $token->__toString(),
                'socket' => $socket . sprintf('/api/servers/%s/ws', $server->uuid),
            ],
        ]);
    }
}
