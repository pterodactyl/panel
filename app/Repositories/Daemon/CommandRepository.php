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

use GuzzleHttp\Client;
use Pterodactyl\Models;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;

class CommandRepository
{
    protected $server;

    public function __construct($server)
    {
        $this->server = ($server instanceof Models\Server) ? $server : Models\Server::findOrFail($server);
    }

    /**
     * [send description].
     * @param  string   $command
     * @return bool
     * @throws DisplayException
     * @throws RequestException
     */
    public function send($command)
    {
        // We don't use the user's specific daemon secret here since we
        // are assuming that a call to this function has been validated.
        // Additionally not all calls to this will be from a logged in user.
        // (e.g. task queue or API)
        try {
            $response = $this->server->node->guzzleClient([
                'X-Access-Token' => $this->server->daemonSecret,
                'X-Access-Server' => $this->server->uuid,
            ])->request('POST', '/server/command', ['json' => ['command' => $command]]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new DisplayException('Command sending responded with a non-200 error code.');
            }

            return $response->getBody();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }
}
