<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Allocations;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;

interface UnassignsAllocations
{
    /**
     * @throws DisplayException
     */
    public function unassign(Server $server, Allocation $allocation): Allocation;
}
