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

use Log;
use Alert;
use Javascript;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Allocation;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\NodeRepository;
use Pterodactyl\Services\Nodes\UpdateService;
use Pterodactyl\Services\Nodes\CreationService;
use Pterodactyl\Services\Nodes\DeletionService;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Cache\Repository as CacheRepository;
use Pterodactyl\Http\Requests\Admin\NodeFormRequest;
use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class NodesController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

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
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * @var \Pterodactyl\Services\Nodes\UpdateService
     */
    protected $updateService;

    public function __construct(
        AlertsMessageBag $alert,
        CacheRepository $cache,
        CreationService $creationService,
        DeletionService $deletionService,
        LocationRepositoryInterface $locationRepository,
        NodeRepositoryInterface $repository,
        Translator $translator,
        UpdateService $updateService
    ) {
        $this->alert = $alert;
        $this->cache = $cache;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->locationRepository = $locationRepository;
        $this->repository = $repository;
        $this->translator = $translator;
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
            $this->alert->warning($this->translator->trans('admin/node.notices.location_required'))->flash();

            return redirect()->route('admin.locations');
        }

        return view('admin.nodes.new', ['locations' => $locations]);
    }

    /**
     * Post controller to create a new node on the system.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\NodeFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(NodeFormRequest $request)
    {
        $node = $this->creationService->handle($request->normalize());
        $this->alert->info($this->translator->trans('admin/node.notices.node_created'))->flash();

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
     * @param  \Pterodactyl\Http\Requests\Admin\NodeFormRequest $request
     * @param \Pterodactyl\Models\Node                          $node
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function updateSettings(NodeFormRequest $request, Node $node)
    {
        $this->updateService->handle($node, $request->normalize());
        $this->alert->success($this->translator->trans('admin/node.notices.node_updated'))->flash();

        return redirect()->route('admin.nodes.view.settings', $node->id)->withInput();
    }

    /**
     * Removes a single allocation from a node.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $node
     * @param  int                      $allocation
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function allocationRemoveSingle(Request $request, $node, $allocation)
    {
        $query = Allocation::where('node_id', $node)->whereNull('server_id')->where('id', $allocation)->delete();
        if ($query < 1) {
            return response()->json([
                'error' => 'Unable to find an allocation matching those details to delete.',
            ], 400);
        }

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
        $query = Allocation::where('node_id', $node)
            ->whereNull('server_id')
            ->where('ip', $request->input('ip'))
            ->delete();
        if ($query < 1) {
            Alert::danger('There was an error while attempting to delete allocations on that IP.')->flash();
        } else {
            Alert::success('Deleted all unallocated ports for <code>' . $request->input('ip') . '</code>.')->flash();
        }

        return redirect()->route('admin.nodes.view.allocation', $node);
    }

    /**
     * Sets an alias for a specific allocation on a node.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $node
     * @return \Illuminate\Http\Response
     */
    public function allocationSetAlias(Request $request, $node)
    {
        if (! $request->input('allocation_id')) {
            return response('Missing required parameters.', 422);
        }

        try {
            $update = Allocation::findOrFail($request->input('allocation_id'));
            $update->ip_alias = (empty($request->input('alias'))) ? null : $request->input('alias');
            $update->save();

            return response('', 204);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Creates new allocations on a node.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $node
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createAllocation(Request $request, $node)
    {
        $repo = new NodeRepository;

        try {
            $repo->addAllocations($node, $request->intersect(['allocation_ip', 'allocation_alias', 'allocation_ports']));
            Alert::success('Successfully added new allocations!')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()
                ->route('admin.nodes.view.allocation', $node)
                ->withErrors(json_decode($ex->getMessage()))
                ->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attempting to add allocations this node. This error has been logged.')
                ->flash();
        }

        return redirect()->route('admin.nodes.view.allocation', $node);
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
        $this->alert->success($this->translator->trans('admin/node.notices.node_deleted'))->flash();

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
