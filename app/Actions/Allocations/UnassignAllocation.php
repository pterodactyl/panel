<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Allocations;

use Pterodactyl\Contracts\Allocations\UnassignsAllocations;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;

final class UnassignAllocation implements UnassignsAllocations
{
    /**
     * @throws DisplayException
     */
    public function unassign(Server $server, Allocation $allocation): Allocation
    {
        throw_if(empty($server->allocation_limit), DisplayException::class, 'You cannot delete allocations for this server: no allocation limit is set.');
        throw_if($allocation->id === $server->allocation_id, DisplayException::class, 'You cannot delete the primary allocation for this server.');

        Allocation::query()->where('id', $allocation->id)->update([
            'notes' => null,
            'server_id' => null,
        ]);

        return $allocation->refresh();
    }
}
