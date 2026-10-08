<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Allocations;

use Pterodactyl\Contracts\Allocations\UpdatesAllocations;
use Pterodactyl\Models\Allocation;

final class UpdateAllocation implements UpdatesAllocations
{
    public function update(Allocation $allocation, ?string $alias): Allocation
    {
        $allocation->forceFill([
            'ip_alias' => empty($alias) ? null : $alias,
        ])->saveOrFail();

        return $allocation;
    }
}
