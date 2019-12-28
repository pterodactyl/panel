<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Cake\Chronos\Chronos;
use Lcobucci\JWT\Builder;
use Illuminate\Http\Request;
use Lcobucci\JWT\Signer\Key;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Lcobucci\JWT\Signer\Hmac\Sha256;
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
     * Generates a one-time token that is sent along in every websocket call to the Daemon.
     * This is a signed JWT that the Daemon then uses the verify the user's identity, and
     * allows us to continually renew this token and avoid users mainitaining sessions wrongly,
     * as well as ensure that user's only perform actions they're allowed to.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, Server $server)
    {
        if (! $request->user()->can('websocket.*', $server)) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN, 'You do not have permission to connect to this server\'s websocket.'
            );
        }

        $now = Chronos::now();

        $signer = new Sha256;

        $token = (new Builder)->issuedBy(config('app.url'))
            ->permittedFor($server->node->getConnectionAddress())
            ->identifiedBy(hash('sha256', $request->user()->id . $server->uuid), true)
            ->issuedAt($now->getTimestamp())
            ->canOnlyBeUsedAfter($now->getTimestamp())
            ->expiresAt($now->addMinutes(15)->getTimestamp())
            ->withClaim('user_id', $request->user()->id)
            ->withClaim('server_uuid', $server->uuid)
            ->withClaim('permissions', array_merge([
                'connect',
                'send-command',
                'send-power',
            ], $request->user()->root_admin ? ['receive-errors'] : []))
            ->getToken($signer, new Key($server->node->daemonSecret));

        $socket = str_replace(['https://', 'http://'], ['wss://', 'ws://'], $server->node->getConnectionAddress());

        return JsonResponse::create([
            'data' => [
                'token' => $token->__toString(),
                'socket' => $socket . sprintf('/api/servers/%s/ws', $server->uuid),
            ],
        ]);
    }
}
