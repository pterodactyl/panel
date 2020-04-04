<?php

namespace Pterodactyl\Http\Controllers\Admin\Servers;

use Illuminate\Bus\Dispatcher;
use Illuminate\Http\Request;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Jobs\Server\TransferJob;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Eloquent\LocationRepository;
use Pterodactyl\Repositories\Eloquent\NodeRepository;

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
     * ServerTransferController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag $alert
     * @param \Illuminate\Bus\Dispatcher $dispatcher
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     * @param \Pterodactyl\Repositories\Eloquent\LocationRepository $locationRepository
     * @param \Pterodactyl\Repositories\Eloquent\NodeRepository $nodeRepository
     */
    public function __construct(
        AlertsMessageBag $alert,
        Dispatcher $dispatcher,
        ServerRepository $repository,
        LocationRepository $locationRepository,
        NodeRepository $nodeRepository
    ) {
        $this->alert = $alert;
        $this->dispatcher = $dispatcher;
        $this->repository = $repository;
        $this->locationRepository = $locationRepository;
        $this->nodeRepository = $nodeRepository;
    }

    /**
     * Starts a transfer of a server to a new node.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
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
            // TODO: Run TransferJob.
            $this->dispatcher->dispatch(new TransferJob($server, $node, $allocation_id, $additional_allocations));

            $this->alert->success(trans('admin/server.alerts.transfer_started'))->flash();
        } else {
            $this->alert->danger(trans('admin/server.alerts.transfer_not_viable'))->flash();
        }

        return redirect()->route('admin.servers.view.manage', $server->id);
    }
}
