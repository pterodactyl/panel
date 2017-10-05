<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository\Daemon;

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
    public function sendSignal($signal);
}
