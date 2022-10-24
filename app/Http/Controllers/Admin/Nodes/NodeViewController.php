<?php

namespace Pterodactyl\Http\Controllers\Admin\Nodes;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Illuminate\Support\Collection;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Repositories\Eloquent\LocationRepository;
use Pterodactyl\Repositories\Eloquent\AllocationRepository;

class NodeViewController extends Controller
{
    use JavascriptInjection;

    /**
     * NodeViewController constructor.
     */
    public function __construct(
        private AllocationRepository $allocationRepository,
        private LocationRepository $locationRepository,
        private ServerRepository $serverRepository,
        private SoftwareVersionService $versionService,
        private ViewFactory $view
    ) {
    }

    /**
     * Returns index view for a specific node on the system.
     */
    public function index(Request $request, Node $node): View
    {
        $node->load('location')->loadCount('servers');

        $stats = Node::query()
            ->selectRaw('IFNULL(SUM(servers.memory), 0) as sum_memory, IFNULL(SUM(servers.disk), 0) as sum_disk')
            ->join('servers', 'servers.node_id', '=', 'nodes.id')
            ->where('node_id', '=', $node->id)
            ->first();

        $usageStats = Collection::make(['disk' => $stats->sum_disk, 'memory' => $stats->sum_memory])
            ->mapWithKeys(function ($value, $key) use ($node) {
                $maxUsage = $node->{$key};
                if ($node->{$key . '_overallocate'} > 0) {
                    $maxUsage = $node->{$key} * (1 + ($node->{$key . '_overallocate'} / 100));
                }

                $percent = ($value / $maxUsage) * 100;

                return [
                    $key => [
                        'value' => number_format($value),
                        'max' => number_format($maxUsage),
                        'percent' => $percent,
                        'css' => ($percent <= self::THRESHOLD_PERCENTAGE_LOW) ? 'green' : (($percent > self::THRESHOLD_PERCENTAGE_MEDIUM) ? 'red' : 'yellow'),
                    ],
                ];
            })
            ->toArray();

        return $this->view->make('admin.nodes.view.index', [
            'node' => $node,
            'stats' => $usageStats,
            'version' => $this->versionService,
        ]);
    }

    /**
     * Returns the settings page for a specific node.
     */
    public function settings(Request $request, Node $node): View
    {
        return $this->view->make('admin.nodes.view.settings', [
            'node' => $node,
            'locations' => $this->locationRepository->all(),
        ]);
    }

    /**
     * Return the node configuration page for a specific node.
     */
    public function configuration(Request $request, Node $node): View
    {
        return $this->view->make('admin.nodes.view.configuration', compact('node'));
    }

    /**
     * Return the node allocation management page.
     */
    public function allocations(Request $request, Node $node): View
    {
        $node->setRelation(
            'allocations',
            $node->allocations()
                ->orderByRaw('server_id IS NOT NULL DESC, server_id IS NULL')
                ->orderByRaw('INET_ATON(ip) ASC')
                ->orderBy('port')
                ->with('server:id,name')
                ->paginate(50)
        );

        $this->plainInject(['node' => Collection::wrap($node)->only(['id'])]);

        return $this->view->make('admin.nodes.view.allocation', [
            'node' => $node,
            'allocations' => Allocation::query()->where('node_id', $node->id)
                ->groupBy('ip')
                ->orderByRaw('INET_ATON(ip) ASC')
                ->get(['ip']),
        ]);
    }

    /**
     * Return a listing of servers that exist for this specific node.
     */
    public function servers(Request $request, Node $node): View
    {
        $this->plainInject([
            'node' => Collection::wrap($node->makeVisible(['daemon_token_id', 'daemon_token']))
                ->only(['scheme', 'fqdn', 'daemonListen', 'daemon_token_id', 'daemon_token']),
        ]);

        return $this->view->make('admin.nodes.view.servers', [
            'node' => $node,
            'servers' => $this->serverRepository->loadAllServersForNode($node->id, 25),
        ]);
    }
}
