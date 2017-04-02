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

namespace Pterodactyl\Repositories\Daemon;

use Pterodactyl\Models;
use GuzzleHttp\Exception\ConnectException;
use Pterodactyl\Exceptions\DisplayException;

class CommandRepository
{
    /**
     * The Eloquent Model associated with the requested server.
     *
     * @var \Pterodactyl\Models\Server
     */
    protected $server;

    /**
     * Constuctor for repository.
     *
     * @param  int|\Pterodactyl\Models\Server  $server
     * @return void
     */
    public function __construct($server)
    {
        $this->server = ($server instanceof Models\Server) ? $server : Models\Server::findOrFail($server);
    }

    /**
     * Sends a command to the daemon.
     *
     * @param  string  $command
     * @return string
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \GuzzleHttp\Exception\RequestException
     */
    public function send($command)
    {
        // We don't use the user's specific daemon secret here since we
        // are assuming that a call to this function has been validated.
        try {
            $response = $this->server->guzzleClient()->request('PUT', '/server/command', [
                'http_errors' => false,
                'json' => [
                    'command' => $command,
                ],
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new DisplayException('Command sending responded with a non-200 error code (HTTP/' . $response->getStatusCode() . ').');
            }

            return $response->getBody();
        } catch (ConnectException $ex) {
            throw $ex;
        }
    }
}
