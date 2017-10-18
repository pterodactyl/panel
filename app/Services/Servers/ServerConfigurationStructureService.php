<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class ServerConfigurationStructureService
{
    const REQUIRED_RELATIONS = ['allocation', 'allocations', 'pack', 'option'];

    /**
     * @var \Pterodactyl\Services\Servers\EnvironmentService
     */
    protected $environment;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * ServerConfigurationStructureService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     * @param \Pterodactyl\Services\Servers\EnvironmentService            $environment
     */
    public function __construct(
        ServerRepositoryInterface $repository,
        EnvironmentService $environment
    ) {
        $this->repository = $repository;
        $this->environment = $environment;
    }

    /**
     * @param int|\Pterodactyl\Models\Server $server
     * @return array
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($server): array
    {
        if (! $server instanceof Server || array_diff(self::REQUIRED_RELATIONS, $server->getRelations())) {
            $server = $this->repository->getDataForCreation(is_digit($server) ? $server : $server->id);
        }

        return [
            'uuid' => $server->uuid,
            'user' => $server->username,
            'build' => [
                'default' => [
                    'ip' => $server->allocation->ip,
                    'port' => $server->allocation->port,
                ],
                'ports' => $server->allocations->groupBy('ip')->map(function ($item) {
                    return $item->pluck('port');
                })->toArray(),
                'env' => $this->environment->process($server),
                'memory' => (int) $server->memory,
                'swap' => (int) $server->swap,
                'io' => (int) $server->io,
                'cpu' => (int) $server->cpu,
                'disk' => (int) $server->disk,
                'image' => $server->image,
            ],
            'keys' => [],
            'service' => [
                'egg' => $server->egg->uuid,
                'pack' => object_get($server, 'pack.uuid'),
                'skip_scripts' => $server->skip_scripts,
            ],
            'rebuild' => false,
            'suspended' => (int) $server->suspended,
        ];
    }
}
