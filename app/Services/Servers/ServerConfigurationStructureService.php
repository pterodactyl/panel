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
    const REQUIRED_RELATIONS = ['allocation', 'allocations', 'pack', 'egg'];

    /**
     * @var \Pterodactyl\Services\Servers\EnvironmentService
     */
    private $environment;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * ServerConfigurationStructureService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     * @param \Pterodactyl\Services\Servers\EnvironmentService $environment
     */
    public function __construct(
        ServerRepositoryInterface $repository,
        EnvironmentService $environment
    ) {
        $this->repository = $repository;
        $this->environment = $environment;
    }

    /**
     * Return a configuration array for a specific server when passed a server model.
     *
     * DO NOT MODIFY THIS FUNCTION. This powers legacy code handling for the new Wings
     * daemon, if you modify the structure eggs will break unexpectedly.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool $legacy
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Server $server, bool $legacy = false): array
    {
        $server->loadMissing(self::REQUIRED_RELATIONS);

        return $legacy ?
            $this->returnLegacyFormat($server)
            : $this->returnCurrentFormat($server);
    }

    /**
     * Returns the new data format used for the Wings daemon.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    protected function returnCurrentFormat(Server $server)
    {
        $mounts = $server->mounts;
        foreach ($mounts as $mount) {
            unset($mount->id);
            unset($mount->uuid);
            unset($mount->name);
            unset($mount->description);
            $mount->read_only = $mount->read_only == 1;
            unset($mount->user_mountable);
            unset($mount->pivot);
        }

        return [
            'uuid' => $server->uuid,
            'suspended' => (bool) $server->suspended,
            'environment' => $this->environment->handle($server),
            'invocation' => $server->startup,
            'build' => [
                'memory_limit' => $server->memory,
                'swap' => $server->swap,
                'io_weight' => $server->io,
                'cpu_limit' => $server->cpu,
                'threads' => $server->threads,
                'disk_space' => $server->disk,
            ],
            'service' => [
                'egg' => $server->egg->uuid,
                'pack' => $server->pack ? $server->pack->uuid : null,
                'skip_scripts' => $server->skip_scripts,
            ],
            'container' => [
                'image' => $server->image,
                'oom_disabled' => $server->oom_disabled,
                'requires_rebuild' => false,
            ],
            'allocations' => [
                'default' => [
                    'ip' => $server->allocation->ip,
                    'port' => $server->allocation->port,
                ],
                'mappings' => $server->getAllocationMappings(),
            ],
            'mounts' => $mounts,
        ];
    }

    /**
     * Returns the legacy server data format to continue support for old egg configurations
     * that have not yet been updated.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    protected function returnLegacyFormat(Server $server)
    {
        return [
            'uuid' => $server->uuid,
            'build' => [
                'default' => [
                    'ip' => $server->allocation->ip,
                    'port' => $server->allocation->port,
                ],
                'ports' => $server->allocations->groupBy('ip')->map(function ($item) {
                    return $item->pluck('port');
                })->toArray(),
                'env' => $this->environment->handle($server),
                'oom_disabled' => $server->oom_disabled,
                'memory' => (int) $server->memory,
                'swap' => (int) $server->swap,
                'io' => (int) $server->io,
                'cpu' => (int) $server->cpu,
                'threads' => $server->threads,
                'disk' => (int) $server->disk,
                'image' => $server->image,
            ],
            'service' => [
                'egg' => $server->egg->uuid,
                'pack' => $server->pack ? $server->pack->uuid : null,
                'skip_scripts' => $server->skip_scripts,
            ],
            'rebuild' => false,
            'suspended' => (int) $server->suspended,
        ];
    }
}
