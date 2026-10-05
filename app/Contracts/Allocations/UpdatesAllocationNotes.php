<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Allocations;

use Pterodactyl\Models\Allocation;

interface UpdatesAllocationNotes
{
    public function update(Allocation $allocation, ?string $notes): Allocation;
}
