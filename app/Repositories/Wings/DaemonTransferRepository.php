<?php

namespace Pterodactyl\Repositories\Wings;

use Lcobucci\JWT\Token\Plain;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\GuzzleException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class DaemonTransferRepository extends DaemonRepository
{
    /**
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function notify(Server $server, Plain $token): void
    {
        try {
            $this->getHttpClient()->post('/api/transfer', [
                'json' => [
                    'server_id' => $server->uuid,
                    'url' => $server->node->getConnectionAddress() . sprintf('/api/servers/%s/archive', $server->uuid),
                    'token' => 'Bearer ' . $token->toString(),
                    'server' => [
                        'uuid' => $server->uuid,
                        'start_on_completion' => false,
                    ],
                ],
            ]);
        } catch (GuzzleException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
