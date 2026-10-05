<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Allocations;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;
use Throwable;

interface AssignsAllocations
{
    /**
     * @throws DisplayException
     * @throws Throwable
     */
    public function assign(Server $server): Allocation;
}
