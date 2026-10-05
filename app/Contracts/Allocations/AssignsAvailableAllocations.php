<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Allocations;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;

interface AssignsAvailableAllocations
{
    /** @throws DisplayException */
    public function assignAvailable(Server $server): Allocation;
}
