<?php

namespace Pterodactyl\Repositories\Wings;

use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class DaemonRevocationRepository extends DaemonRepository
{
    /**
     * Deauthorizes a user (disconnects websockets and SFTP) on the Wings instance for
     * the provided servers. If no servers are provided, the user is deauthorized on all
     * servers on the instance.
     *
     * @param string[] $servers
     */
    public function deauthorize(string $user, array $servers = []): void
    {
        try {
            $this->getHttpClient()->post('/api/deauthorize-user', [
                'json' => ['user' => $user, 'servers' => $servers],
            ]);
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
