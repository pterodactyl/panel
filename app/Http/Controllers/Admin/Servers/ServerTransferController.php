<?php

namespace Pterodactyl\Http\Controllers\Admin\Servers;

use Illuminate\Bus\Dispatcher;
use Illuminate\Http\Request;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Eloquent\LocationRepository;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Services\Servers\TransferService;

class ServerTransferController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Illuminate\Bus\Dispatcher
     */
    private $dispatcher;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\LocationRepository
     */
    private $locationRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\NodeRepository
     */
    private $nodeRepository;

    /**
     * @var \Pterodactyl\Services\Servers\SuspensionService
     */
    private $suspensionService;

    /**
     * @var \Pterodactyl\Services\Servers\TransferService
     */
    private $transferService;

    /**
     * ServerTransferController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag $alert
     * @param \Illuminate\Bus\Dispatcher $dispatcher
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     * @param \Pterodactyl\Repositories\Eloquent\LocationRepository $locationRepository
     * @param \Pterodactyl\Repositories\Eloquent\NodeRepository $nodeRepository
     * @param \Pterodactyl\Services\Servers\SuspensionService $suspensionService
     * @param \Pterodactyl\Services\Servers\TransferService $transferService
     */
    public function __construct(
        AlertsMessageBag $alert,
        Dispatcher $dispatcher,
        ServerRepository $repository,
        LocationRepository $locationRepository,
        NodeRepository $nodeRepository,
        SuspensionService $suspensionService,
        TransferService $transferService
    ) {
        $this->alert = $alert;
        $this->dispatcher = $dispatcher;
        $this->repository = $repository;
        $this->locationRepository = $locationRepository;
        $this->nodeRepository = $nodeRepository;
        $this->suspensionService = $suspensionService;
        $this->transferService = $transferService;
    }

    /**
     * Starts a transfer of a server to a new node.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function transfer(Request $request, Server $server)
    {
        $validatedData = $request->validate([
            'node_id' => 'required|exists:nodes,id',
            'allocation_id' => 'required|bail|unique:servers|exists:allocations,id',
            'allocation_additional' => 'nullable',
        ]);

        $node_id = $validatedData['node_id'];
        $allocation_id = $validatedData['allocation_id'];
        $additional_allocations = $validatedData['allocation_additional'] ?? [];

        // Check if the node is viable for the transfer.
        $node = $this->nodeRepository->getNodeWithResourceUsage($node_id);
        if ($node->isViable($server->memory, $server->disk)) {
            // Suspend the server and request an archive to be created.
            // $this->suspensionService->toggle($server, 'suspend');
            $this->transferService->requestArchive($server);

            $this->alert->success(trans('admin/server.alerts.transfer_started'))->flash();
        } else {
            $this->alert->danger(trans('admin/server.alerts.transfer_not_viable'))->flash();
        }

        return redirect()->route('admin.servers.view.manage', $server->id);
    }
}
