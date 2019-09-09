<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Cake\Chronos\Chronos;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Cache\Repository;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class WebsocketController extends ClientApiController
{
    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

    /**
     * WebsocketController constructor.
     *
     * @param \Illuminate\Contracts\Cache\Repository $cache
     */
    public function __construct(Repository $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Generates a one-time token that is sent along in the request to the Daemon. The
     * daemon then connects back to the Panel to verify that the token is valid when it
     * is used.
     *
     * This token is valid for 30 seconds from time of generation, it is not designed
     * to be stored and used over and over.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, Server $server)
    {
        if (! $request->user()->can('connect-to-ws', $server)) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN, 'You do not have permission to connect to this server\'s websocket.'
            );
        }

        $token = Str::random(32);

        $this->cache->put('ws:' . $token, [
            'user_id' => $request->user()->id,
            'server_id' => $server->id,
            'request_ip' => $request->ip(),
            'timestamp' => Chronos::now()->toIso8601String(),
        ], Chronos::now()->addSeconds(30));

        $socket = str_replace(['https://', 'http://'], ['wss://', 'ws://'], $server->node->getConnectionAddress());

        return JsonResponse::create([
            'data' => [
                'socket' => $socket . sprintf('/api/servers/%s/ws/%s', $server->uuid, $token),
            ],
        ]);
    }
}
