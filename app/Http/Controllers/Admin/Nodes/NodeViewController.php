<?php

namespace Pterodactyl\Http\Controllers\Admin\Nodes;

use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Illuminate\Support\Collection;
use Pterodactyl\Models\Allocation;
use Illuminate\Contracts\View\Factory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Repositories\Eloquent\LocationRepository;
use Pterodactyl\Repositories\Eloquent\AllocationRepository;

class NodeViewController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\NodeRepository
     */
    private $repository;

    /**
     * @var \Illuminate\Contracts\View\Factory
     */
    private $view;

    /**
     * @var \Pterodactyl\Services\Helpers\SoftwareVersionService
     */
    private $versionService;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\LocationRepository
     */
    private $locationRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\AllocationRepository
     */
    private $allocationRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $serverRepository;

    /**
     * NodeViewController constructor.
     */
    public function __construct(
        AllocationRepository $allocationRepository,
        LocationRepository $locationRepository,
        NodeRepository $repository,
        ServerRepository $serverRepository,
        SoftwareVersionService $versionService,
        Factory $view
    ) {
        $this->repository = $repository;
        $this->view = $view;
        $this->versionService = $versionService;
        $this->locationRepository = $locationRepository;
        $this->allocationRepository = $allocationRepository;
        $this->serverRepository = $serverRepository;
    }

    /**
     * Returns index view for a specific node on the system.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request, Node $node)
    {
        $node = $this->repository->loadLocationAndServerCount($node);

        return $this->view->make('admin.nodes.view.index', [
            'node' => $node,
            'stats' => $this->repository->getUsageStats($node),
            'version' => $this->versionService,
        ]);
    }

    /**
     * Returns the settings page for a specific node.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function settings(Request $request, Node $node)
    {
        return $this->view->make('admin.nodes.view.settings', [
            'node' => $node,
            'locations' => $this->locationRepository->all(),
            'deployable' => $node->deployable,
        ]);
    }

    /**
     * Return the node configuration page for a specific node.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function configuration(Request $request, Node $node)
    {
        return $this->view->make('admin.nodes.view.configuration', compact('node'));
    }

    /**
     * Return the node allocation management page.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function allocations(Request $request, Node $node)
    {
        $node = $this->repository->loadNodeAllocations($node);

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
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function servers(Request $request, Node $node)
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
