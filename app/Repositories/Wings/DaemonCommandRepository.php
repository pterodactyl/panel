<?php

namespace Pterodactyl\Repositories\Wings;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Psr\Http\Message\ResponseInterface;

class DaemonCommandRepository extends DaemonRepository
{
    /**
     * Sends a command or multiple commands to a running server instance.
     *
     * @param string|string[] $command
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send($command): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        return $this->getHttpClient()->post(
            sprintf('/api/servers/%s/commands', $this->server->uuid),
            [
                'json' => ['commands' => is_array($command) ? $command : [$command]],
            ]
        );
    }
}
