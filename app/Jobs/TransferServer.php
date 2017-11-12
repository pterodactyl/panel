<?php

namespace Pterodactyl\Jobs;

use Pterodactyl\Models\Node;
use Illuminate\Bus\Queueable;
use Pterodactyl\Models\Server;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Servers\ServerDeletionService;

class TransferServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $server;
    private $node;

    /**
     * Create a new job instance.
     *
     * @param Server $serverToTransfer
     * @param Node $newNode
     */
    public function __construct(Server $serverToTransfer, Node $newNode)
    {
        $this->server = $serverToTransfer;
        $this->node = $newNode;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ServerCreationService $creation, ServerDeletionService $deletion, SuspensionService $suspension)
    {
        $server = $this->server;
        $newNode = $this->node;

        // 1. Suspend Old Server
        $suspension->toggle($server, 'suspend');

        // 2. Zip Folder
        $backup = $server->generateBackup();

        // 3. Transfer Zip File
        $archive = $newNode->transfer($backup);

        // 4. Verify File Hash
        if ($backup->hash !== $archive->hash) {
            $archive->delete();
            abort(500, 'File transfer corrupted, please try again.');
        }

        // 5. Unzip File
        $archive->extract();

        // 6. Update Settings on New Node
        $newServerDetails = $server->toArray();
        $newServerDetails['node_id'] = $newNode->id;
        $newServer = $creation->create($newServerDetails);

        // 7. Verify Server Status
        if (! $newServer->isWorking()) {
            $deletion->withForce()->handle($newServer);
            abort(500, 'Server failed to startup, please try again.');
        }

        // 8. Unsuspend Old Server
        $deletion->withForce()->handle($server);
        $suspension->toggle($server, 'unsuspend');
    }
}
