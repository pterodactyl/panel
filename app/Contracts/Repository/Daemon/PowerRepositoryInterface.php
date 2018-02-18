<?php

namespace Pterodactyl\Contracts\Repository\Daemon;

use Psr\Http\Message\ResponseInterface;

interface PowerRepositoryInterface extends BaseRepositoryInterface
{
    const SIGNAL_START = 'start';
    const SIGNAL_STOP = 'stop';
    const SIGNAL_RESTART = 'restart';
    const SIGNAL_KILL = 'kill';

    /**
     * Send a power signal to a server.
     *
     * @param string $signal
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \Pterodactyl\Exceptions\Repository\Daemon\InvalidPowerSignalException
     */
    public function sendSignal(string $signal): ResponseInterface;
}
