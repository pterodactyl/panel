<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Permission;
use Illuminate\Contracts\Cache\Repository;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Services\Servers\GetUserPermissionsService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class WebsocketController extends ClientApiController
{
    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

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
     * @param \Illuminate\Contracts\Cache\Repository $cache
     */
    public function __construct(
        NodeJWTService $jwtService,
        GetUserPermissionsService $permissionsService,
        Repository $cache
    ) {
        parent::__construct();

        $this->cache = $cache;
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

        $token = $this->jwtService
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
            ->setClaims([
                'user_id' => $request->user()->id,
                'server_uuid' => $server->uuid,
                'permissions' => $this->permissionsService->handle($server, $user),
            ])
            ->handle($server->node, $user->id . $server->uuid);

        $socket = str_replace(['https://', 'http://'], ['wss://', 'ws://'], $server->node->getConnectionAddress());

        return JsonResponse::create([
            'data' => [
                'token' => $token->__toString(),
                'socket' => $socket . sprintf('/api/servers/%s/ws', $server->uuid),
            ],
        ]);
    }
}
