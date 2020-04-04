<?php

namespace Pterodactyl\Jobs\Server;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Servers\ServerDeletionService;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Services\Servers\TransferService;

class TransferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $server, $node, $allocation_id, $additional_allocations;

    /**
     * Create a new job instance.
     *
     * @param Server $serverToTransfer
     * @param Node $newNode
     */
    public function __construct(Server $serverToTransfer, Node $newNode, int $allocation_id, array $additional_allocations)
    {
        $this->server = $serverToTransfer;
        $this->node = $newNode;
        $this->allocation_id = $allocation_id;
        $this->additional_allocations = $additional_allocations;
    }

    /**
     * Execute the job.
     *
     * @param ServerCreationService $creationService
     * @param ServerDeletionService $deletionService
     * @param SuspensionService $suspensionService
     * @param TransferService $transferService
     * @return void
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Throwable
     */
    public function handle(
        ServerCreationService $creationService,
        ServerDeletionService $deletionService,
        SuspensionService $suspensionService,
        TransferService $transferService
    ) {
        //$server = $this->server;
        //$newNode = $this->node;

        // 1. Suspend Old Server
        //$suspensionService->toggle($server, 'suspend');

        // 2. Zip Folder
        //$backup = $server->generateBackup();

        // 3. Transfer Zip File
        //$archive = $newNode->transfer($backup);

        // 4. Verify File Hash
        /*if ($backup->hash !== $archive->hash) {
            $archive->delete();
            abort(500, 'File transfer corrupted, please try again.');
        }*/

        // 5. Unzip File
        //$archive->extract();

        // 6. Update Settings on New Node
        //$newServerDetails = $server->toArray();
        //$newServerDetails['node_id'] = $newNode->id;
        //$newServer = $creationService->create($newServerDetails);

        // 7. Verify Server Status
        /*if (!$newServer->isWorking()) {
            $deletionService->withForce()->handle($newServer);
            abort(500, 'Server failed to startup, please try again.');
        }*/

        // 8. Unsuspend Old Server
        //$deletionService->withForce()->handle($server);
        //$suspensionService->toggle($server, 'unsuspend');
    }
}
