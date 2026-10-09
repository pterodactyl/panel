<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Allocations;

use Pterodactyl\Contracts\Allocations\UpdatesAllocationNotes;
use Pterodactyl\Models\Allocation;

final class UpdateAllocationNotes implements UpdatesAllocationNotes
{
    public function update(Allocation $allocation, ?string $notes): Allocation
    {
        $allocation->forceFill(['notes' => $notes])->save();

        return $allocation;
    }
}
