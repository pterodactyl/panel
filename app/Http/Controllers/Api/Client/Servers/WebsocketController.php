<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Permission;
use Illuminate\Contracts\Cache\Repository;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
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
     * WebsocketController constructor.
     *
     * @param \Pterodactyl\Services\Nodes\NodeJWTService $jwtService
     * @param \Illuminate\Contracts\Cache\Repository $cache
     */
    public function __construct(NodeJWTService $jwtService, Repository $cache)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->jwtService = $jwtService;
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
        if ($user->cannot(Permission::ACTION_WEBSOCKET, $server)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'You do not have permission to connect to this server\'s websocket.');
        }

        if ($user->root_admin || $user->id === $server->owner_id) {
            $permissions = ['*'];

            if ($user->root_admin) {
                $permissions[] = 'admin.errors';
                $permissions[] = 'admin.install';
            }
        } else {
            /** @var \Pterodactyl\Models\Subuser|null $subuserPermissions */
            $subuserPermissions = $server->subusers->first(function (Subuser $subuser) use ($user) {
                return $subuser->user_id === $user->id;
            });

            $permissions = $subuserPermissions ? $subuserPermissions->permissions : [];
        }

        $token = $this->jwtService
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
            ->setClaims([
                'user_id' => $request->user()->id,
                'server_uuid' => $server->uuid,
                'permissions' => $permissions ?? [],
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
