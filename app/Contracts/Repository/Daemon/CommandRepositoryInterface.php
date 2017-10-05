<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository\Daemon;

interface CommandRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Send a command to a server.
     *
     * @param string $command
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send($command);
}
