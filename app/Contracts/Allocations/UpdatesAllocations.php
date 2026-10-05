<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Allocations;

use Pterodactyl\Models\Allocation;

interface UpdatesAllocations
{
    public function update(Allocation $allocation, ?string $alias): Allocation;
}
