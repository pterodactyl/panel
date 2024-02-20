<?php

namespace Pterodactyl\Services\Telemetry;

use Exception;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Arr;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Location;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\Allocation;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Repositories\Eloquent\SettingsRepository;
use Pterodactyl\Repositories\Wings\DaemonConfigurationRepository;

class TelemetryCollectionService
{
    /**
     * TelemetryCollectionService constructor.
     */
    public function __construct(
        private DaemonConfigurationRepository $daemonConfigurationRepository,
        private SettingsRepository $settingsRepository
    ) {
    }

    /**
     * Collects telemetry data and sends it to the Pterodactyl Telemetry Service.
     */
    public function __invoke(): void
    {
        try {
            $data = $this->collect();
        } catch (Exception) {
            return;
        }

        Http::post('https://telemetry.pterodactyl.io', $data);
    }

    /**
     * Collects telemetry data and returns it as an array.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function collect(): array
    {
        $uuid = $this->settingsRepository->get('app:telemetry:uuid');
        if (is_null($uuid)) {
            $uuid = Uuid::uuid4()->toString();
            $this->settingsRepository->set('app:telemetry:uuid', $uuid);
        }

        $nodes = Node::all()->map(function ($node) {
            try {
                $info = $this->daemonConfigurationRepository->setNode($node)->getSystemInformation(2);
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
        })->filter(fn ($node) => !is_null($node))->toArray();

        return [
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
                        'version' => DB::getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION),
                    ],
                ],
            ],

            'resources' => [
                'allocations' => [
                    'count' => Allocation::count(),
                    'used' => Allocation::whereNotNull('server_id')->count(),
                ],

                'backups' => [
                    'count' => Backup::count(),
                    'bytes' => Backup::sum('bytes'),
                ],

                'eggs' => [
                    'count' => Egg::count(),
                    // Egg UUIDs are generated randomly on import, so there is not a consistent way to
                    // determine if servers are using default eggs or not.
//                    'server_usage' => Egg::all()
//                        ->flatMap(fn (Egg $egg) => [$egg->uuid => $egg->servers->count()])
//                        ->filter(fn (int $count) => $count > 0)
//                        ->toArray(),
                ],

                'locations' => [
                    'count' => Location::count(),
                ],

                'mounts' => [
                    'count' => Mount::count(),
                ],

                'nests' => [
                    'count' => Nest::count(),
                    // Nest UUIDs are generated randomly on import, so there is not a consistent way to
                    // determine if servers are using default eggs or not.
//                    'server_usage' => Nest::all()
//                        ->flatMap(fn (Nest $nest) => [$nest->uuid => $nest->eggs->sum(fn (Egg $egg) => $egg->servers->count())])
//                        ->filter(fn (int $count) => $count > 0)
//                        ->toArray(),
                ],

                'nodes' => [
                    'count' => Node::count(),
                ],

                'servers' => [
                    'count' => Server::count(),
                    'suspended' => Server::where('status', Server::STATUS_SUSPENDED)->count(),
                ],

                'users' => [
                    'count' => User::count(),
                    'admins' => User::where('root_admin', true)->count(),
                ],
            ],

            'nodes' => $nodes,
        ];
    }
}
