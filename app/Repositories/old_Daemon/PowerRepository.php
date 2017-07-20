<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Repositories\old_Daemon;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\ConnectException;
use Pterodactyl\Exceptions\DisplayException;

class PowerRepository
{
    /**
     * The Eloquent Model associated with the requested server.
     *
     * @var \Pterodactyl\Models\Server
     */
    protected $server;

    /**
     * The Eloquent Model associated with the user to run the request as.
     *
     * @var \Pterodactyl\Models\User|null
     */
    protected $user;

    /**
     * Constuctor for repository.
     *
     * @param  \Pterodactyl\Models\Server  $server
     * @param  \Pterodactyl\Models\User|null   $user
     * @return void
     */
    public function __construct(Server $server, User $user = null)
    {
        $this->server = $server;
        $this->user = $user;
    }

    /**
     * Sends a power option to the daemon.
     *
     * @param  string                    $action
     * @return string
     *
     * @throws \GuzzleHttp\Exception\RequestException
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function do($action)
    {
        try {
            $response = $this->server->guzzleClient($this->user)->request('PUT', '/server/power', [
                'http_errors' => false,
                'json' => [
                    'action' => $action,
                ],
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new DisplayException('Power toggle endpoint responded with a non-200 error code (HTTP/' . $response->getStatusCode() . ').');
            }

            return $response->getBody();
        } catch (ConnectException $ex) {
            throw $ex;
        }
    }

    /**
     * Starts a server.
     *
     * @return void
     */
    public function start()
    {
        $this->do('start');
    }

    /**
     * Stops a server.
     *
     * @return void
     */
    public function stop()
    {
        $this->do('stop');
    }

    /**
     * Restarts a server.
     *
     * @return void
     */
    public function restart()
    {
        $this->do('restart');
    }

    /**
     * Kills a server.
     *
     * @return void
     */
    public function kill()
    {
        $this->do('kill');
    }
}
