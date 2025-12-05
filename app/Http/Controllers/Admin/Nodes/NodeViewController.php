<?php

namespace Pterodactyl\Http\Controllers\Admin\Nodes;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Illuminate\Support\Collection;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Repositories\Eloquent\LocationRepository;

class NodeViewController extends Controller
{
    use JavascriptInjection;

    /**
     * NodeViewController constructor.
     */
    public function __construct(
        private LocationRepository $locationRepository,
        private NodeRepository $repository,
        private ServerRepository $serverRepository,
        private SoftwareVersionService $versionService,
    ) {
    }

    /**
     * Returns index view for a specific node on the system.
     */
    public function index(Request $request, Node $node): View
    {
        $node = $this->repository->loadLocationAndServerCount($node);

        return view('admin.nodes.view.index', [
            'node' => $node,
            'stats' => $this->repository->getUsageStats($node),
            'version' => $this->versionService,
        ]);
    }

    /**
     * Returns the settings page for a specific node.
     */
    public function settings(Request $request, Node $node): View
    {
        return view('admin.nodes.view.settings', [
            'node' => $node,
            'locations' => $this->locationRepository->all(),
        ]);
    }

    /**
     * Return the node configuration page for a specific node.
     */
    public function configuration(Request $request, Node $node): View
    {
        return view('admin.nodes.view.configuration', compact('node'));
    }

    /**
     * Return the node allocation management page.
     */
    public function allocations(Request $request, Node $node): View
    {
        $node = $this->repository->loadNodeAllocations($node);

        $this->plainInject(['node' => Collection::make([$node])->only(['id'])]);

        return view('admin.nodes.view.allocation', [
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
            'node' => Collection::make([$node->makeVisible(['daemon_token_id', 'daemon_token'])])
                ->only(['scheme', 'fqdn', 'daemonListen', 'daemon_token_id', 'daemon_token']),
        ]);

        return view('admin.nodes.view.servers', [
            'node' => $node,
            'servers' => $this->serverRepository->loadAllServersForNode($node->id, 25),
        ]);
    }
}
