<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Http\Controllers\Admin;

use Javascript;
use App\Models\Node;
use App\Models\Allocation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Prologue\Alerts\AlertsMessageBag;
use App\Services\Nodes\NodeUpdateService;
use App\Services\Nodes\NodeCreationService;
use App\Services\Nodes\NodeDeletionService;
use App\Services\Allocations\AssignmentService;
use App\Services\Helpers\SoftwareVersionService;
use App\Http\Requests\Admin\Node\NodeFormRequest;
use Illuminate\Cache\Repository as CacheRepository;
use App\Contracts\Repository\NodeRepositoryInterface;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Http\Requests\Admin\Node\AllocationFormRequest;
use App\Services\Allocations\AllocationDeletionService;
use App\Contracts\Repository\LocationRepositoryInterface;
use App\Contracts\Repository\AllocationRepositoryInterface;
use App\Http\Requests\Admin\Node\AllocationAliasFormRequest;

class NodesController extends Controller
{
    /**
     * @var \App\Services\Allocations\AllocationDeletionService
     */
    protected $allocationDeletionService;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \App\Contracts\Repository\AllocationRepositoryInterface
     */
    protected $allocationRepository;

    /**
     * @var \App\Services\Allocations\AssignmentService
     */
    protected $assignmentService;

    /**
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * @var \App\Services\Nodes\NodeCreationService
     */
    protected $creationService;

    /**
     * @var \App\Services\Nodes\NodeDeletionService
     */
    protected $deletionService;

    /**
     * @var \App\Contracts\Repository\LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var \App\Contracts\Repository\NodeRepositoryInterface
     */
    protected $repository;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \App\Services\Nodes\NodeUpdateService
     */
    protected $updateService;

    /**
     * @var \App\Services\Helpers\SoftwareVersionService
     */
    protected $versionService;

    /**
     * NodesController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                               $alert
     * @param \App\Services\Allocations\AllocationDeletionService     $allocationDeletionService
     * @param \App\Contracts\Repository\AllocationRepositoryInterface $allocationRepository
     * @param \App\Services\Allocations\AssignmentService             $assignmentService
     * @param \Illuminate\Cache\Repository                                    $cache
     * @param \App\Services\Nodes\NodeCreationService                 $creationService
     * @param \App\Services\Nodes\NodeDeletionService                 $deletionService
     * @param \App\Contracts\Repository\LocationRepositoryInterface   $locationRepository
     * @param \App\Contracts\Repository\NodeRepositoryInterface       $repository
     * @param \App\Contracts\Repository\ServerRepositoryInterface     $serverRepository
     * @param \App\Services\Nodes\NodeUpdateService                   $updateService
     * @param \App\Services\Helpers\SoftwareVersionService            $versionService
     */
    public function __construct(
        AlertsMessageBag $alert,
        AllocationDeletionService $allocationDeletionService,
        AllocationRepositoryInterface $allocationRepository,
        AssignmentService $assignmentService,
        CacheRepository $cache,
        NodeCreationService $creationService,
        NodeDeletionService $deletionService,
        LocationRepositoryInterface $locationRepository,
        NodeRepositoryInterface $repository,
        ServerRepositoryInterface $serverRepository,
        NodeUpdateService $updateService,
        SoftwareVersionService $versionService
    ) {
        $this->alert = $alert;
        $this->allocationDeletionService = $allocationDeletionService;
        $this->allocationRepository = $allocationRepository;
        $this->assignmentService = $assignmentService;
        $this->cache = $cache;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->locationRepository = $locationRepository;
        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
        $this->updateService = $updateService;
        $this->versionService = $versionService;
    }

    /**
     * Displays the index page listing all nodes on the panel.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('admin.nodes.index', [
            'nodes' => $this->repository->setSearchTerm($request->input('query'))->getNodeListingData(),
        ]);
    }

    /**
     * Displays create new node page.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function create()
    {
        $locations = $this->locationRepository->all();
        if (count($locations) < 1) {
            $this->alert->warning(trans('admin/node.notices.location_required'))->flash();

            return redirect()->route('admin.locations');
        }

        return view('admin.nodes.new', ['locations' => $locations]);
    }

    /**
     * Post controller to create a new node on the system.
     *
     * @param \App\Http\Requests\Admin\Node\NodeFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     */
    public function store(NodeFormRequest $request)
    {
        $node = $this->creationService->handle($request->normalize());
        $this->alert->info(trans('admin/node.notices.node_created'))->flash();

        return redirect()->route('admin.nodes.view.allocation', $node->id);
    }

    /**
     * Shows the index overview page for a specific node.
     *
     * @param \App\Models\Node $node
     * @return \Illuminate\View\View
     */
    public function viewIndex(Node $node)
    {
        return view('admin.nodes.view.index', [
            'node' => $this->repository->loadLocationAndServerCount($node),
            'stats' => $this->repository->getUsageStats($node),
            'version' => $this->versionService,
        ]);
    }

    /**
     * Shows the settings page for a specific node.
     *
     * @param \App\Models\Node $node
     * @return \Illuminate\View\View
     */
    public function viewSettings(Node $node)
    {
        return view('admin.nodes.view.settings', [
            'node' => $node,
            'locations' => $this->locationRepository->all(),
        ]);
    }

    /**
     * Shows the configuration page for a specific node.
     *
     * @param \App\Models\Node $node
     * @return \Illuminate\View\View
     */
    public function viewConfiguration(Node $node)
    {
        return view('admin.nodes.view.configuration', ['node' => $node]);
    }

    /**
     * Shows the allocation page for a specific node.
     *
     * @param \App\Models\Node $node
     * @return \Illuminate\View\View
     */
    public function viewAllocation(Node $node)
    {
        $this->repository->loadNodeAllocations($node);
        Javascript::put(['node' => collect($node)->only(['id'])]);

        return view('admin.nodes.view.allocation', [
            'allocations' => $this->allocationRepository->setColumns(['ip'])->getUniqueAllocationIpsForNode($node->id),
            'node' => $node,
        ]);
    }

    /**
     * Shows the server listing page for a specific node.
     *
     * @param \App\Models\Node $node
     * @return \Illuminate\View\View
     */
    public function viewServers(Node $node)
    {
        $servers = $this->serverRepository->loadAllServersForNode($node->id, 25);
        Javascript::put([
            'node' => collect($node->makeVisible('daemonSecret'))->only(['scheme', 'fqdn', 'daemonListen', 'daemonSecret']),
        ]);

        return view('admin.nodes.view.servers', ['node' => $node, 'servers' => $servers]);
    }

    /**
     * Updates settings for a node.
     *
     * @param \App\Http\Requests\Admin\Node\NodeFormRequest $request
     * @param \App\Models\Node                              $node
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function updateSettings(NodeFormRequest $request, Node $node)
    {
        $this->updateService->handle($node, $request->normalize(), $request->input('reset_secret') === 'on');
        $this->alert->success(trans('admin/node.notices.node_updated'))->flash();

        return redirect()->route('admin.nodes.view.settings', $node->id)->withInput();
    }

    /**
     * Removes a single allocation from a node.
     *
     * @param int                            $node
     * @param \App\Models\Allocation $allocation
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function allocationRemoveSingle(int $node, Allocation $allocation): Response
    {
        $this->allocationDeletionService->handle($allocation);

        return response('', 204);
    }

    /**
     * Removes multiple individual allocations from a node.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $node
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function allocationRemoveMultiple(Request $request, int $node): Response
    {
        $allocations = $request->input('allocations');
        foreach ($allocations as $rawAllocation) {
            $allocation = new Allocation();
            $allocation->id = $rawAllocation['id'];
            $this->allocationRemoveSingle($node, $allocation);
        }

        return response('', 204);
    }

    /**
     * Remove all allocations for a specific IP at once on a node.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $node
     * @return \Illuminate\Http\RedirectResponse
     */
    public function allocationRemoveBlock(Request $request, $node)
    {
        $this->allocationRepository->deleteWhere([
            ['node_id', '=', $node],
            ['server_id', '=', null],
            ['ip', '=', $request->input('ip')],
        ]);

        $this->alert->success(trans('admin/node.notices.unallocated_deleted', ['ip' => $request->input('ip')]))
            ->flash();

        return redirect()->route('admin.nodes.view.allocation', $node);
    }

    /**
     * Sets an alias for a specific allocation on a node.
     *
     * @param \App\Http\Requests\Admin\Node\AllocationAliasFormRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function allocationSetAlias(AllocationAliasFormRequest $request)
    {
        $this->allocationRepository->update($request->input('allocation_id'), [
            'ip_alias' => (empty($request->input('alias'))) ? null : $request->input('alias'),
        ]);

        return response('', 204);
    }

    /**
     * Creates new allocations on a node.
     *
     * @param \App\Http\Requests\Admin\Node\AllocationFormRequest $request
     * @param int|\App\Models\Node                                $node
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @throws \App\Exceptions\Service\Allocation\InvalidPortMappingException
     * @throws \App\Exceptions\Service\Allocation\PortOutOfRangeException
     * @throws \App\Exceptions\Service\Allocation\TooManyPortsInRangeException
     */
    public function createAllocation(AllocationFormRequest $request, Node $node)
    {
        $this->assignmentService->handle($node, $request->normalize());
        $this->alert->success(trans('admin/node.notices.allocations_added'))->flash();

        return redirect()->route('admin.nodes.view.allocation', $node->id);
    }

    /**
     * Deletes a node from the system.
     *
     * @param $node
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\DisplayException
     */
    public function delete($node)
    {
        $this->deletionService->handle($node);
        $this->alert->success(trans('admin/node.notices.node_deleted'))->flash();

        return redirect()->route('admin.nodes');
    }

    /**
     * Returns the configuration token to auto-deploy a node.
     *
     * @param \App\Models\Node $node
     * @return \Illuminate\Http\JsonResponse
     */
    public function setToken(Node $node)
    {
        $token = bin2hex(random_bytes(16));
        $this->cache->put('Node:Configuration:' . $token, $node->id, now()->addMinutes(5));

        return response()->json(['token' => $token]);
    }
}
