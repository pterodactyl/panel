<?php

namespace Pterodactyl\Repositories\Wings;

use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class DaemonTransferRepository extends DaemonRepository
{

    /**
     * @param Server $server
     * @param string $token
     *
     * @throws DaemonConnectionException
     */
    public function notify(Server $server, string $token): void
    {
        try {
            $this->getHttpClient()->post('/api/transfer', [
                'json' => [
                    'url' => $server->node->getConnectionAddress() . sprintf('/api/servers/%s/archive', $server->uuid),
                    'token' => $token,
                ],
            ]);
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
