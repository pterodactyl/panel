<?php

namespace Pterodactyl\Repositories\Daemon;

use Psr\Http\Message\ResponseInterface;
use Pterodactyl\Contracts\Repository\Daemon\CommandRepositoryInterface;

class CommandRepository extends BaseRepository implements CommandRepositoryInterface
{
    /**
     * Send a command to a server.
     *
     * @param string $command
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send(string $command): ResponseInterface
    {
        return $this->getHttpClient()->request('POST', 'server/command', [
            'json' => [
                'command' => $command,
            ],
        ]);
    }
}
