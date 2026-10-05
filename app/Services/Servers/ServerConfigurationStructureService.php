<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Servers;

use Illuminate\Database\Eloquent\Collection;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Server;
use UnexpectedValueException;

class ServerConfigurationStructureService
{
    /**
     * ServerConfigurationStructureService constructor.
     */
    public function __construct(private readonly EnvironmentService $environment) {}

    /**
     * Return a configuration array for a specific server when passed a server model.
     *
     * DO NOT MODIFY THIS FUNCTION. This powers legacy code handling for the new Wings
     * daemon, if you modify the structure eggs will break unexpectedly.
     *
     * @param  array<string, JsonValue>  $override  server attributes to override on a cloned model
     * @return array<string, JsonValue>
     */
    public function handle(Server $server, array $override = [], bool $legacy = false): array
    {
        $clone = $server;
        // If any overrides have been set on this call make sure to update them on the
        // cloned instance so that the configuration generated uses them.
        if ($override !== []) {
            $clone = $server->fresh() ?? throw new UnexpectedValueException('The server no longer exists in the database.');
            foreach ($override as $key => $value) {
                $clone->setAttribute($key, $value);
            }
        }

        $clone->loadMissing(['egg', 'allocation', 'allocations', 'mounts']);
        $egg = $clone->egg ?? throw new UnexpectedValueException('The server does not have an egg relationship.');
        $allocation = $clone->allocation ?? throw new UnexpectedValueException('The server does not have a primary allocation.');

        return $legacy
            ? $this->returnLegacyFormat($clone, $egg, $allocation)
            : $this->returnCurrentFormat($clone, $egg, $allocation);
    }

    /**
     * Returns the new data format used for the Wings daemon.
     *
     * @return array<string, JsonValue>
     */
    protected function returnCurrentFormat(Server $server, Egg $egg, Allocation $allocation): array
    {
        return [
            'uuid' => $server->uuid,
            'meta' => [
                'name' => $server->name,
                'description' => $server->description,
            ],
            'suspended' => $server->isSuspended(),
            'environment' => $this->environment->handle($server),
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
     * Returns the legacy server data format to continue support for old egg configurations
     * that have not yet been updated.
     *
     * @return array<string, JsonValue>
     *
     * @deprecated
     */
    protected function returnLegacyFormat(Server $server, Egg $egg, Allocation $allocation): array
    {
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
                'egg' => $egg->uuid,
                'skip_scripts' => $server->skip_scripts,
            ],
            'rebuild' => false,
            'suspended' => $server->isSuspended() ? 1 : 0,
        ];
    }
}
