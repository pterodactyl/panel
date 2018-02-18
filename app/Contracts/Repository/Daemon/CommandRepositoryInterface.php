<?php

namespace Pterodactyl\Contracts\Repository\Daemon;

use Psr\Http\Message\ResponseInterface;

interface CommandRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Send a command to a server.
     *
     * @param string $command
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send(string $command): ResponseInterface;
}
