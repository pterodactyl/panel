<?php

namespace Pterodactyl\Repositories\Wings;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class DaemonBackupRepository extends DaemonRepository
{
    /**
     * Returns a stream of a backup's contents from the Wings instance so that we
     * do not need to send the user directly to the Daemon.
     *
     * @param string $backup
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function getBackup(string $backup): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            return $this->getHttpClient()->get(
                sprintf('/api/servers/%s/backup/%s', $this->server->uuid, $backup),
                ['stream' => true]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
