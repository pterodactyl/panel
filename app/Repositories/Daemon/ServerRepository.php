<?php
/*
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

use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface as DatabaseServerRepositoryInterface;
use Pterodactyl\Services\Servers\EnvironmentService;

class ServerRepository extends BaseRepository implements ServerRepositoryInterface
{
    const DAEMON_PERMISSIONS = ['s:*'];

    /**
     * {@inheritdoc}
     */
    public function create($id, $overrides = [], $start = false)
    {
        $repository = $this->app->make(DatabaseServerRepositoryInterface::class);
        $environment = $this->app->make(EnvironmentService::class);

        $server = $repository->getDataForCreation($id);

        $data = [
            'uuid' => (string) $server->uuid,
            'user' => $server->username,
            'build' => [
                'default' => [
                    'ip' => $server->allocation->ip,
                    'port' => $server->allocation->port,
                ],
                'ports' => $server->allocations->groupBy('ip')->map(function ($item) {
                    return $item->pluck('port');
                })->toArray(),
                'env' => $environment->process($server),
                'memory' => (int) $server->memory,
                'swap' => (int) $server->swap,
                'io' => (int) $server->io,
                'cpu' => (int) $server->cpu,
                'disk' => (int) $server->disk,
                'image' => (int) $server->image,
            ],
            'service' => [
                'type' => $server->option->service->folder,
                'option' => $server->option->tag,
                'pack' => object_get($server, 'pack.uuid'),
                'skip_scripts' => $server->skip_scripts,
            ],
            'rebuild' => false,
            'start_on_completion' => $start,
            'keys' => [
                (string) $server->daemonSecret => self::DAEMON_PERMISSIONS,
            ],
        ];

        // Loop through overrides.
        foreach ($overrides as $key => $value) {
            array_set($data, $key, $value);
        }

        return $this->getHttpClient()->request('POST', '/servers', [
            'json' => $data,
        ]);
    }
}
