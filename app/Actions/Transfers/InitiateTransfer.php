<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Transfers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Transfers\FailsTransfers;
use Pterodactyl\Contracts\Transfers\InitiatesTransfers;
use Pterodactyl\Enum\JwtScope;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Throwable;

final readonly class InitiateTransfer implements InitiatesTransfers
{
    public function __construct(
        private NodeJWTService $nodeJWTService,
        private FailsTransfers $failTransfer,
    ) {}

    /**
     * Records the transfer, reserves the target allocations, and tells the destination
     * node to begin pulling the server. Wings is called after the records commit; if it
     * rejects the transfer, it is marked failed and its allocations are released.
     *
     * @param  list<int>  $additionalAllocations
     *
     * @throws DisplayException
     * @throws Throwable
     */
    public function initiate(Server $server, Node $node, int $allocationId, array $additionalAllocations): ServerTransfer
    {
        $transfer = DB::transaction(function () use ($server, $node, $allocationId, $additionalAllocations): ServerTransfer {
            $server = $server->newQuery()->whereKey($server->getKey())->lockForUpdate()->firstOrFail();
            throw_unless($node->isViable($server->memory, $server->disk), DisplayException::class, trans('admin/server.alerts.transfer_not_viable'));
            $server->validateTransferState();

            $allocationIds = array_values(array_unique([$allocationId, ...$additionalAllocations]));
            $allocations = Allocation::query()
                ->whereKey($allocationIds)
                ->where('node_id', $node->id)
                ->unassigned()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            throw_unless($allocations->count() === count($allocationIds), DisplayException::class, 'The selected allocations are no longer available on the target node.');

            $transfer = ServerTransfer::query()->create([
                'server_id' => $server->id,
                'old_node' => $server->node_id,
                'new_node' => $node->id,
                'old_allocation' => $server->allocation_id,
                'new_allocation' => $allocationId,
                'old_additional_allocations' => $server->allocations
                    ->reject(fn (Allocation $allocation): bool => $allocation->id === $server->allocation_id)
                    ->pluck('id')
                    ->values()
                    ->all(),
                'new_additional_allocations' => $additionalAllocations,
            ]);

            // Assign now so another server can't claim these allocations mid-transfer.
            Allocation::query()->whereKey($allocationIds)->update(['server_id' => $server->id]);

            return $transfer;
        });

        try {
            $token = $this->nodeJWTService
                ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
                ->setSubject($server->uuid)
                ->setScopes(JwtScope::ServerTransfer)
                ->handle($node, $server->uuid);

            Daemon::server($server)->transfer($node, $token);
        } catch (Throwable $throwable) {
            $this->failTransfer->fail($transfer);

            throw $throwable;
        }

        return $transfer->refresh();
    }
}
