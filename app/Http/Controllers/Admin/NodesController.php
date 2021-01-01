<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Illuminate\Http\Response;
use Pterodactyl\Models\Allocation;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Nodes\NodeUpdateService;
use Illuminate\Cache\Repository as CacheRepository;
use Pterodactyl\Services\Nodes\NodeCreationService;
use Pterodactyl\Services\Nodes\NodeDeletionService;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Http\Requests\Admin\Node\NodeFormRequest;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Node\AllocationFormRequest;
use Pterodactyl\Services\Allocations\AllocationDeletionService;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Node\AllocationAliasFormRequest;

class NodesController extends Controller
{
    /**
     * @var \Pterodactyl\Services\Allocations\AllocationDeletionService
     */
    protected $allocationDeletionService;

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
     * @var \Pterodactyl\Services\Nodes\NodeCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Services\Nodes\NodeDeletionService
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
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Services\Nodes\NodeUpdateService
     */
    protected $updateService;

    /**
     * @var \Pterodactyl\Services\Helpers\SoftwareVersionService
     */
    protected $versionService;

    /**
     * NodesController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag $alert
     * @param \Pterodactyl\Services\Allocations\AllocationDeletionService $allocationDeletionService
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface $allocationRepository
     * @param \Pterodactyl\Services\Allocations\AssignmentService $assignmentService
     * @param \Illuminate\Cache\Repository $cache
     * @param \Pterodactyl\Services\Nodes\NodeCreationService $creationService
     * @param \Pterodactyl\Services\Nodes\NodeDeletionService $deletionService
     * @param \Pterodactyl\Contracts\Repository\LocationRepositoryInterface $locationRepository
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface $repository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $serverRepository
     * @param \Pterodactyl\Services\Nodes\NodeUpdateService $updateService
     * @param \Pterodactyl\Services\Helpers\SoftwareVersionService $versionService
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
     * @param \Pterodactyl\Http\Requests\Admin\Node\NodeFormRequest $request
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
     * Updates settings for a node.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Node\NodeFormRequest $request
     * @param \Pterodactyl\Models\Node $node
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
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
     * @param int $node
     * @param \Pterodactyl\Models\Allocation $allocation
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function allocationRemoveSingle(int $node, Allocation $allocation): Response
    {
        $this->allocationDeletionService->handle($allocation);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Removes multiple individual allocations from a node.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $node
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function allocationRemoveMultiple(Request $request, int $node): Response
    {
        $allocations = $request->input('allocations');
        foreach ($allocations as $rawAllocation) {
            $allocation = new Allocation();
            $allocation->id = $rawAllocation['id'];
            $this->allocationRemoveSingle($node, $allocation);
        }

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Remove all allocations for a specific IP at once on a node.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $node
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
     * @param \Pterodactyl\Http\Requests\Admin\Node\AllocationAliasFormRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function allocationSetAlias(AllocationAliasFormRequest $request)
    {
        $this->allocationRepository->update($request->input('allocation_id'), [
            'ip_alias' => (empty($request->input('alias'))) ? null : $request->input('alias'),
        ]);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Creates new allocations on a node.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Node\AllocationFormRequest $request
     * @param int|\Pterodactyl\Models\Node $node
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\InvalidPortMappingException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\PortOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\TooManyPortsInRangeException
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
}
