<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Allocations;

use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;

interface SetsPrimaryAllocations
{
    public function setPrimary(Server $server, Allocation $allocation): Allocation;
}
