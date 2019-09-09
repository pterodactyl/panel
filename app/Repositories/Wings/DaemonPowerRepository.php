<?php

namespace Pterodactyl\Repositories\Wings;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Psr\Http\Message\ResponseInterface;

class DaemonPowerRepository extends DaemonRepository
{
    /**
     * Sends a power action to the server instance.
     *
     * @param string $action
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send(string $action): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        return $this->getHttpClient()->post(
            sprintf('/api/servers/%s/power', $this->server->uuid),
            ['json' => ['action' => $action]]
        );
    }
}
