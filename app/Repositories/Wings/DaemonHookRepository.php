<?php

namespace Pterodactyl\Repositories\Wings;

use Illuminate\Support\Collection;
use Pterodactyl\Events\Server\Power;
use Pterodactyl\Jobs\Hook\SyncHooksJob;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class DaemonHookRepository extends DaemonRepository
{
    /**
     * Sends a power action to the server instance.
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function send(Collection $hooks): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/hooks', $this->server->uuid),
                ['json' => ['data' => $hooks]]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
