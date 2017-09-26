<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Daemon;

use Webmozart\Assert\Assert;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface as DatabaseServerRepositoryInterface;

class ServerRepository extends BaseRepository implements ServerRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($id, array $overrides = [], $start = false)
    {
        Assert::numeric($id, 'First argument passed to create must be numeric, received %s.');
        Assert::boolean($start, 'Third argument passed to create must be boolean, received %s.');

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
                'image' => $server->image,
            ],
            'service' => [
                'type' => $server->option->service->folder,
                'option' => $server->option->tag,
                'pack' => object_get($server, 'pack.uuid'),
                'skip_scripts' => $server->skip_scripts,
            ],
            'rebuild' => false,
            'start_on_completion' => $start,
        ];

        // Loop through overrides.
        foreach ($overrides as $key => $value) {
            array_set($data, $key, $value);
        }

        return $this->getHttpClient()->request('POST', '/servers', [
            'json' => $data,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $data)
    {
        return $this->getHttpClient()->request('PATCH', '/server', [
            'json' => $data,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function reinstall($data = null)
    {
        Assert::nullOrIsArray($data, 'First argument passed to reinstall must be null or an array, received %s.');

        if (is_null($data)) {
            return $this->getHttpClient()->request('POST', '/server/reinstall');
        }

        return $this->getHttpClient()->request('POST', '/server/reinstall', [
            'json' => $data,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function rebuild()
    {
        return $this->getHttpClient()->request('POST', '/server/rebuild');
    }

    /**
     * {@inheritdoc}
     */
    public function suspend()
    {
        return $this->getHttpClient()->request('POST', '/server/suspend');
    }

    /**
     * {@inheritdoc}
     */
    public function unsuspend()
    {
        return $this->getHttpClient()->request('POST', '/server/unsuspend');
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        return $this->getHttpClient()->request('DELETE', '/servers');
    }

    /**
     * {@inheritdoc}
     */
    public function details()
    {
        return $this->getHttpClient()->request('GET', '/servers');
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessKey($key)
    {
        Assert::stringNotEmpty($key, 'First argument passed to revokeAccessKey must be a non-empty string, received %s.');

        return $this->getHttpClient()->request('DELETE', '/keys/' . $key);
    }
}
