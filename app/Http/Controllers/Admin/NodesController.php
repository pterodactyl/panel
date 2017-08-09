<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Javascript;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Nodes\UpdateService;
use Pterodactyl\Services\Nodes\CreationService;
use Pterodactyl\Services\Nodes\DeletionService;
use Illuminate\Cache\Repository as CacheRepository;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Http\Requests\Admin\Node\NodeFormRequest;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Node\AllocationFormRequest;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Node\AllocationAliasFormRequest;

class NodesController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    protected $allocationRepository;

    /**
     * @var \Pterodactyl\Services\Allocations\AssignmentService
     */
    protected $assignmentService;

    /**
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * @var \Pterodactyl\Services\Nodes\CreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Services\Nodes\DeletionService
     */
    protected $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Nodes\UpdateService
     */
    protected $updateService;

    /**
     * NodesController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                               $alert
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface $allocationRepository
     * @param \Pterodactyl\Services\Allocations\AssignmentService             $assignmentService
     * @param \Illuminate\Cache\Repository                                    $cache
     * @param \Pterodactyl\Services\Nodes\CreationService                     $creationService
     * @param \Pterodactyl\Services\Nodes\DeletionService                     $deletionService
     * @param \Pterodactyl\Contracts\Repository\LocationRepositoryInterface   $locationRepository
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface       $repository
     * @param \Pterodactyl\Services\Nodes\UpdateService                       $updateService
     */
    public function __construct(
        AlertsMessageBag $alert,
        AllocationRepositoryInterface $allocationRepository,
        AssignmentService $assignmentService,
        CacheRepository $cache,
        CreationService $creationService,
        DeletionService $deletionService,
        LocationRepositoryInterface $locationRepository,
        NodeRepositoryInterface $repository,
        UpdateService $updateService
    ) {
        $this->alert = $alert;
        $this->allocationRepository = $allocationRepository;
        $this->assignmentService = $assignmentService;
        $this->cache = $cache;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->locationRepository = $locationRepository;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Displays the index page listing all nodes on the panel.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('admin.nodes.index', [
            'nodes' => $this->repository->search($request->input('query'))->getNodeListingData(),
        ]);
    }

    /**
     * Displays create new node page.
     *
     * @return  \Illuminate\Http\RedirectResponse|\Illuminate\View\View
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
     * @param  \Pterodactyl\Http\Requests\Admin\Node\NodeFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
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
     * @param  int $node
     * @return \Illuminate\View\View
     */
    public function viewIndex($node)
    {
        return view('admin.nodes.view.index', [
            'node' => $this->repository->getSingleNode($node),
            'stats' => $this->repository->getUsageStats($node),
        ]);
    }

    /**
     * Shows the settings page for a specific node.
     *
     * @param  \Pterodactyl\Models\Node $node
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
     * @param  \Pterodactyl\Models\Node $node
     * @return \Illuminate\View\View
     */
    public function viewConfiguration(Node $node)
    {
        return view('admin.nodes.view.configuration', ['node' => $node]);
    }

    /**
     * Shows the allocation page for a specific node.
     *
     * @param  int $node
     * @return \Illuminate\View\View
     */
    public function viewAllocation($node)
    {
        $node = $this->repository->getNodeAllocations($node);
        Javascript::put(['node' => collect($node)->only(['id'])]);

        return view('admin.nodes.view.allocation', ['node' => $node]);
    }

    /**
     * Shows the server listing page for a specific node.
     *
     * @param  int $node
     * @return \Illuminate\View\View
     */
    public function viewServers($node)
    {
        $node = $this->repository->getNodeServers($node);
        Javascript::put([
            'node' => collect($node->makeVisible('daemonSecret'))->only(['scheme', 'fqdn', 'daemonListen', 'daemonSecret']),
        ]);

        return view('admin.nodes.view.servers', ['node' => $node]);
    }

    /**
     * Updates settings for a node.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\Node\NodeFormRequest $request
     * @param  \Pterodactyl\Models\Node                              $node
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function updateSettings(NodeFormRequest $request, Node $node)
    {
        $this->updateService->handle($node, $request->normalize());
        $this->alert->success(trans('admin/node.notices.node_updated'))->flash();

        return redirect()->route('admin.nodes.view.settings', $node->id)->withInput();
    }

    /**
     * Removes a single allocation from a node.
     *
     * @param  int $node
     * @param  int $allocation
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function allocationRemoveSingle($node, $allocation)
    {
        $this->allocationRepository->deleteWhere([
            ['id', '=', $allocation],
            ['node_id', '=', $node],
            ['server_id', '=', null],
        ]);

        return response('', 204);
    }

    /**
     * Remove all allocations for a specific IP at once on a node.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $node
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
     * @param  \Pterodactyl\Http\Requests\Admin\Node\AllocationAliasFormRequest $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
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
     * @param  \Pterodactyl\Http\Requests\Admin\Node\AllocationFormRequest $request
     * @param  int|\Pterodactyl\Models\Node                                $node
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
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
     * @throws \Pterodactyl\Exceptions\DisplayException
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
     * @param  \Pterodactyl\Models\Node $node
     * @return \Illuminate\Http\JsonResponse
     */
    public function setToken(Node $node)
    {
        $token = bin2hex(random_bytes(16));
        $this->cache->tags(['Node:Configuration'])->put($token, $node->id, 5);

        return response()->json(['token' => $token]);
    }
}
