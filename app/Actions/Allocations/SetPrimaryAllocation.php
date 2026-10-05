<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Allocations;

use Pterodactyl\Contracts\Allocations\SetsPrimaryAllocations;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;

final class SetPrimaryAllocation implements SetsPrimaryAllocations
{
    public function setPrimary(Server $server, Allocation $allocation): Allocation
    {
        $server->update(['allocation_id' => $allocation->id]);

        return $allocation->refresh();
    }
}
