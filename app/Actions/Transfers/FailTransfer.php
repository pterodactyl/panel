<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Transfers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Contracts\Transfers\FailsTransfers;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\ServerTransfer;

final readonly class FailTransfer implements FailsTransfers
{
    public function fail(ServerTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer): void {
            $transfer->forceFill(['successful' => false])->saveOrFail();

            Allocation::query()
                ->whereIn('id', [$transfer->new_allocation, ...($transfer->new_additional_allocations ?? [])])
                ->update(['server_id' => null]);

            Event::dispatch(new OperationCompleted($transfer->server->uuid, 'transfer', false, $transfer->server->uuid));
        });
    }
}
