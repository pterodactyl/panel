<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Telemetry;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use PDO;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Setting;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;
use Ramsey\Uuid\Uuid;

class TelemetryCollectionService
{
    /**
     * Collects telemetry data and returns it as an array.
     *
     * @return ApiPayload
     */
    public function collect(): array
    {
        $uuid = Setting::fetch('app:telemetry:uuid');
        if (($uuid) === null) {
            $uuid = Uuid::uuid4()->toString();
            Setting::put('app:telemetry:uuid', $uuid);
        }

        $nodes = Node::all()->map(function (Node $node): ?array {
            try {
                $info = Daemon::node($node)->systemInformation(2);
            } catch (Exception) {
                return null;
            }

            return [
                'id' => $node->uuid,
                'version' => Arr::get($info, 'version', ''),

                'docker' => [
                    'version' => Arr::get($info, 'docker.version', ''),

                    'cgroups' => [
                        'driver' => Arr::get($info, 'docker.cgroups.driver', ''),
                        'version' => Arr::get($info, 'docker.cgroups.version', ''),
                    ],

                    'containers' => [
                        'total' => Arr::get($info, 'docker.containers.total', -1),
                        'running' => Arr::get($info, 'docker.containers.running', -1),
                        'paused' => Arr::get($info, 'docker.containers.paused', -1),
                        'stopped' => Arr::get($info, 'docker.containers.stopped', -1),
                    ],

                    'storage' => [
                        'driver' => Arr::get($info, 'docker.storage.driver', ''),
                        'filesystem' => Arr::get($info, 'docker.storage.filesystem', ''),
                    ],

                    'runc' => [
                        'version' => Arr::get($info, 'docker.runc.version', ''),
                    ],
                ],

                'system' => [
                    'architecture' => Arr::get($info, 'system.architecture', ''),
                    'cpuThreads' => Arr::get($info, 'system.cpu_threads', ''),
                    'memoryBytes' => Arr::get($info, 'system.memory_bytes', ''),
                    'kernelVersion' => Arr::get($info, 'system.kernel_version', ''),
                    'os' => Arr::get($info, 'system.os', ''),
                    'osType' => Arr::get($info, 'system.os_type', ''),
                ],
            ];
        })->filter(fn (?array $node): bool => $node !== null)->all();

        $payload = [
            'id' => $uuid,

            'panel' => [
                'version' => config('app.version'),
                'phpVersion' => phpversion(),

                'drivers' => [
                    'backup' => [
                        'type' => config('backups.default'),
                    ],

                    'cache' => [
                        'type' => config('cache.default'),
                    ],

                    'database' => [
                        'type' => config('database.default'),
                        'version' => DB::getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION),
                    ],
                ],
            ],

            'resources' => [
                'allocations' => [
                    'count' => Allocation::query()->count(),
                    'used' => Allocation::query()->whereNotNull('server_id')->count(),
                ],

                'backups' => [
                    'count' => Backup::query()->count(),
                    'bytes' => Backup::query()->sum('bytes'),
                ],

                'eggs' => [
                    'count' => Egg::query()->count(),
                    // Egg UUIDs are generated randomly on import, so there is not a consistent way to
                    // determine if servers are using default eggs or not.
                    //                    'server_usage' => Egg::all()
                    //                        ->flatMap(fn (Egg $egg) => [$egg->uuid => $egg->servers->count()])
                    //                        ->filter(fn (int $count) => $count > 0)
                    //                        ->toArray(),
                ],

                'locations' => [
                    'count' => Location::query()->count(),
                ],

                'mounts' => [
                    'count' => Mount::query()->count(),
                ],

                'nodes' => [
                    'count' => Node::query()->count(),
                ],

                'servers' => [
                    'count' => Server::query()->count(),
                    'suspended' => Server::query()->where('status', Server::STATUS_SUSPENDED)->count(),
                ],

                'users' => [
                    'count' => User::query()->count(),
                    'admins' => User::query()->where('root_admin', true)->count(),
                ],
            ],

            'nodes' => $nodes,
        ];
        JsonValueGuard::assertPayload($payload);

        return $payload;
    }
}
