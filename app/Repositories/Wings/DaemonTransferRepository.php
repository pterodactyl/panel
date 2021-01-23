<?php

namespace Pterodactyl\Repositories\Wings;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class DaemonTransferRepository extends DaemonRepository
{
    /**
     * @throws DaemonConnectionException
     */
    public function notify(Server $server, array $data, Node $node, string $token): void
    {
        try {
            $this->getHttpClient()->post('/api/transfer', [
                'json' => [
                    'server_id' => $server->uuid,
                    'url' => $node->getConnectionAddress() . sprintf('/api/servers/%s/archive', $server->uuid),
                    'token' => 'Bearer ' . $token,
                    'server' => $data,
                ],
            ]);
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
