<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Allocations;

use Pterodactyl\Contracts\Allocations\SetsPrimaryAllocations;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;

final class SetPrimaryAllocation implements SetsPrimaryAllocations
{
    public function setPrimary(Server $server, Allocation $allocation): Allocation
    {
        $server->update(['allocation_id' => $allocation->id]);

        Activity::event('server:allocation.primary')
            ->subject($server, $allocation)
            ->property('allocation', $allocation->toString())
            ->log();

        // The allocation row is unchanged, but a loaded server relation still holds the old primary.
        return $allocation->unsetRelation('server');
    }
}
