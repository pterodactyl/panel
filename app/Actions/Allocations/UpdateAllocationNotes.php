<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Allocations;

use Pterodactyl\Contracts\Allocations\UpdatesAllocationNotes;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Allocation;

final class UpdateAllocationNotes implements UpdatesAllocationNotes
{
    public function update(Allocation $allocation, ?string $notes): Allocation
    {
        $original = $allocation->notes;
        $allocation->forceFill(['notes' => $notes])->save();

        if ($original !== $notes) {
            Activity::event('server:allocation.notes')
                ->subject($allocation->server, $allocation)
                ->property(['allocation' => $allocation->toString(), 'old' => $original, 'new' => $notes])
                ->log();
        }

        return $allocation;
    }
}
