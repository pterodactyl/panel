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

namespace Pterodactyl\Http\Controllers\API\User;

use Log;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\API\BaseController;

class ServerController extends BaseController
{
    public function info(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid)->load('allocations');

        try {
            $response = $server->guzzleClient()->request('GET', '/server');

            $json = json_decode($response->getBody());
            $daemon = [
                'status' => $json->status,
                'stats' => $json->proc,
            ];
        } catch (\Exception $ex) {
            $daemon = [
                'error' => 'An error was encountered while trying to connect to the daemon to collect information. It might be offline.',
            ];
            Log::error($ex);
        }

        return [
            'uuidShort' => $server->uuidShort,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'node' => $server->node->name,
            'limits' => [
                'memory' => $server->memory,
                'swap' => $server->swap,
                'disk' => $server->disk,
                'io' => $server->io,
                'cpu' => $server->cpu,
                'oom_disabled' => (bool) $server->oom_disabled,
            ],
            'allocations' => $server->allocations->map(function ($item) use ($server) {
                return [
                    'ip' => $item->alias,
                    'port' => $item->port,
                    'default' => ($item->id === $server->allocation_id),
                ];
            }),
            'sftp' => [
                'username' => ($request->user()->can('view-sftp', $server)) ? $server->username : null,
            ],
            'daemon' => [
                'token' => $server->daemonSecret,
                'response' => $daemon,
            ],
        ];
    }

    public function power(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        Auth::user()->can('power-' . $request->input('action'), $server);

        $res = $server->guzzleClient()->request('PUT', '/server/power', [
            'exceptions' => false,
            'json' => [
                'action' => $request->input('action'),
            ],
        ]);

        if ($res->getStatusCode() !== 204) {
            return $this->response->error(json_decode($res->getBody())->error, $res->getStatusCode());
        }

        return $this->response->noContent();
    }
}
