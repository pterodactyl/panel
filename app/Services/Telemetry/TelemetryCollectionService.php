<?php

namespace Pterodactyl\Services\Telemetry;

use DB;
use PDO;
use Exception;
use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Location;
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
     * ?
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
     * ?
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function collect(): array
    {
        $uuid = $this->settingsRepository->get('app:uuid');
        if (is_null($uuid)) {
            $uuid = Uuid::uuid4()->toString();
            $this->settingsRepository->set('app:uuid', $uuid);
        }

        $nodes = Node::all()->map(function ($node) {
            try {
                $info = $this->daemonConfigurationRepository->setNode($node)->getSystemInformation();
            } catch (Exception) {
                return null;
            }

            return [
                'id' => $node->uuid,
                'version' => $info['version'],

//                'docker' => [
//                    'version' => '',
//
//                    'cgroups' => [
//                        'driver' => '',
//                        'version' => '',
//                    ],
//
//                    'containers' => [
//                        'running' => 0,
//                        'paused' => 0,
//                        'stopped' => 0,
//                    ],
//
//                    'storage' => [
//                        'driver' => '',
//                        'filesystem' => '',
//                    ],
//
//                    'runc' => [
//                        'version' => '',
//                    ],
//                ],

                'system' => [
                    'architecture' => $info['architecture'],
                    'cpuThreads' => $info['cpu_count'],
//                    'memoryBytes' => -1,
                    'kernelVersion' => $info['kernel_version'],
//                    'os' => '',
                    'osType' => $info['os'],
                ],
            ];
        })->filter(fn($node) => !is_null($node))->toArray();

        $data = [
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
                    'count' => Allocation::count(),
                    'used' => Allocation::whereNotNull('server_id')->count(),
                ],

                'backups' => [
                    'count' => Backup::count(),
                    'bytes' => Backup::sum('bytes'),
                ],

                'eggs' => [
                    'count' => Egg::count(),
                    'ids' => Egg::pluck('uuid')->toArray(),
                ],

                'locations' => [
                    'count' => Location::count(),
                ],

                'mounts' => [
                    'count' => Mount::count(),
                ],

                'nests' => [
                    'count' => Nest::count(),
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

        return $data;
    }
}
