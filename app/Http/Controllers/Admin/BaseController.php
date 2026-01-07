<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Illuminate\Support\Facades\DB;

class BaseController extends Controller
{
    /**
     * BaseController constructor.
     */
    public function __construct(private SoftwareVersionService $version, private ViewFactory $view)
    {
    }

    /**
     * Return the admin index view.
     */
    public function index(): View
    {

        $nodeCount = \Pterodactyl\Models\Node::count();
        $userCount = \Pterodactyl\Models\User::count();
        $serverCount = \Pterodactyl\Models\Server::count();
        $allocationCount = \Pterodactyl\Models\Allocation::count();

        return $this->view->make('admin.index', [
            'version' => $this->version,
            'stats' => [
                'nodes' => $nodeCount,
                'users' => $userCount,
                'servers' => $serverCount,
                'allocations' => $allocationCount,
            ],
            'nodesData' => (function () {
                $nodes = \Pterodactyl\Models\Node::all();


                $serverSums = DB::table('servers')
                    ->select('node_id', DB::raw('COALESCE(SUM(memory), 0) as memory_used'), DB::raw('COALESCE(SUM(disk), 0) as disk_used'), DB::raw('COUNT(*) as servers_count'))
                    ->groupBy('node_id')
                    ->pluck('memory_used', 'node_id')
                    ->toArray();

                $serverDiskSums = DB::table('servers')
                    ->select('node_id', DB::raw('COALESCE(SUM(disk), 0) as disk_used'))
                    ->groupBy('node_id')
                    ->pluck('disk_used', 'node_id')
                    ->toArray();

                // Aggregated allocation counts per node.
                $allocTotals = DB::table('allocations')
                    ->select('node_id', DB::raw('COUNT(*) as total'))
                    ->groupBy('node_id')
                    ->pluck('total', 'node_id')
                    ->toArray();

                $allocUsed = DB::table('allocations')
                    ->select('node_id', DB::raw('COUNT(*) as used'))
                    ->whereNotNull('server_id')
                    ->groupBy('node_id')
                    ->pluck('used', 'node_id')
                    ->toArray();

                $repo = app()->make(\Pterodactyl\Repositories\Wings\DaemonConfigurationRepository::class);

                $result = [];

                foreach ($nodes as $node) {
                    $memoryLimit = (int) ($node->memory * (1 + ($node->memory_overallocate / 100)));
                    $diskLimit = (int) ($node->disk * (1 + ($node->disk_overallocate / 100)));

                    $memoryUsed = (int) ($serverSums[$node->id] ?? 0);
                    $diskUsed = (int) ($serverDiskSums[$node->id] ?? 0);

                    $allocTotal = (int) ($allocTotals[$node->id] ?? 0);
                    $allocUsedCount = (int) ($allocUsed[$node->id] ?? 0);
                    $allocFree = $allocTotal - $allocUsedCount;

                    try {
                        $data = $repo->setNode($node)->getSystemInformation();
                        $status = true;
                    } catch (\Exception $e) {
                        $status = false;
                    }

                    $lastChecked = now()->toDateTimeString();

                    $result[] = [
                        'id' => $node->id,
                        'name' => $node->name,
                        'fqdn' => $node->fqdn,
                        'maintenance' => (bool) $node->maintenance_mode,
                        'memory' => [
                            'limit' => $memoryLimit,
                            'used' => $memoryUsed,
                            'free' => max(0, $memoryLimit - $memoryUsed),
                        ],
                        'disk' => [
                            'limit' => $diskLimit,
                            'used' => $diskUsed,
                            'free' => max(0, $diskLimit - $diskUsed),
                        ],
                        'allocations' => [
                            'total' => $allocTotal,
                            'used' => $allocUsedCount,
                            'free' => $allocFree,
                        ],
                        'online' => (bool) $status,
                        'last_checked' => $lastChecked,
                    ];
                }

                return $result;
            })(),
        ]);
    }
}
