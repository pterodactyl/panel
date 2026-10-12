<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Servers;

use Illuminate\Database\Eloquent\Collection;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Server;
use UnexpectedValueException;

class ServerConfigurationStructureService
{
    public function __construct(private readonly EnvironmentService $environment) {}

    /**
     * The server configuration Wings reads. Its shape is a contract with Wings, so it must not
     * change.
     *
     * @return array<string, mixed>
     */
    public function forServer(Server $server): array
    {
        $server->loadMissing(['egg', 'allocation', 'allocations', 'mounts']);
        $egg = $server->egg ?? throw new UnexpectedValueException('The server does not have an egg relationship.');
        $allocation = $server->allocation ?? throw new UnexpectedValueException('The server does not have a primary allocation.');

        return [
            'uuid' => $server->uuid,
            'meta' => [
                'name' => $server->name,
                'description' => $server->description,
            ],
            'suspended' => $server->isSuspended(),
            'environment' => $this->environment->forServer($server),
            'invocation' => $server->startup,
            'skip_egg_scripts' => $server->skip_scripts,
            'build' => [
                'memory_limit' => $server->memory,
                'swap' => $server->swap,
                'io_weight' => $server->io,
                'cpu_limit' => $server->cpu,
                'threads' => $server->threads,
                'disk_space' => $server->disk,
                'oom_disabled' => $server->oom_disabled,
            ],
            'container' => [
                'image' => $server->image,
                // This field is deprecated - use the value in the "build" block.
                //
                // TODO: remove this key in V2.
                'oom_disabled' => $server->oom_disabled,
                'requires_rebuild' => false,
            ],
            'allocations' => [
                'force_outgoing_ip' => $egg->force_outgoing_ip,
                'default' => [
                    'ip' => $allocation->ip,
                    'port' => $allocation->port,
                ],
                'mappings' => $server->getAllocationMappings(),
            ],
            'mounts' => $server->mounts->map(fn (Mount $mount): array => [
                'source' => $mount->source,
                'target' => $mount->target,
                'read_only' => $mount->read_only,
            ])->values()->all(),
            'egg' => [
                'id' => $egg->uuid,
                'file_denylist' => $egg->inherit_file_denylist,
            ],
        ];
    }

    /**
     * The structure the old daemon read, which egg configuration placeholders such as
     * {{server.build.default.port}} still resolve against.
     *
     * @return array<string, mixed>
     */
    public function legacyForServer(Server $server): array
    {
        $server->loadMissing(['egg', 'allocation', 'allocations']);
        $egg = $server->egg ?? throw new UnexpectedValueException('The server does not have an egg relationship.');
        $allocation = $server->allocation ?? throw new UnexpectedValueException('The server does not have a primary allocation.');

        return [
            'uuid' => $server->uuid,
            'build' => [
                'default' => [
                    'ip' => $allocation->ip,
                    'port' => $allocation->port,
                ],
                'ports' => $server->allocations
                    ->groupBy('ip')
                    ->map(fn (Collection $allocations): array => $allocations
                        ->map(fn (Allocation $allocation): int => $allocation->port)
                        ->values()
                        ->all())
                    ->all(),
                'env' => $this->environment->forServer($server),
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
                'egg' => $egg->uuid,
                'skip_scripts' => $server->skip_scripts,
            ],
            'rebuild' => false,
            'suspended' => $server->isSuspended() ? 1 : 0,
        ];
    }
}
