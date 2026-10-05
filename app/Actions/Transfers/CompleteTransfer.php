<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Transfers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Contracts\Transfers\CompletesTransfers;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerTransfer;
use Throwable;

final readonly class CompleteTransfer implements CompletesTransfers
{
    /**
     * Moves the server onto its new node and allocations, then removes it from the old node.
     *
     * @throws Throwable
     */
    public function complete(ServerTransfer $transfer): Server
    {
        $server = DB::transaction(function () use ($transfer): Server {
            Allocation::query()
                ->whereIn('id', [$transfer->old_allocation, ...($transfer->old_additional_allocations ?? [])])
                ->update(['server_id' => null]);

            $server = $transfer->server;
            $server->update([
                'allocation_id' => $transfer->new_allocation,
                'node_id' => $transfer->new_node,
            ]);

            $transfer->update(['successful' => true]);
            Event::dispatch(new OperationCompleted($server->uuid, 'transfer', true, $server->uuid));

            return $server->refresh();
        });

        // Point the delete at the old node so the freshly transferred copy is left alone.
        try {
            Daemon::server($server, $transfer->oldNode)
                ->delete();
        } catch (DaemonConnectionException $daemonConnectionException) {
            Log::warning($daemonConnectionException->getMessage(), ['exception' => $daemonConnectionException, 'transfer_id' => $transfer->id]);
        }

        return $server;
    }
}
