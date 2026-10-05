<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Allocations;

use Pterodactyl\Contracts\Allocations\DeletesAllocations;
use Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException;
use Pterodactyl\Models\Allocation;

final class DeleteAllocation implements DeletesAllocations
{
    /**
     * Delete an allocation, provided no server is attached to it.
     *
     * @throws ServerUsingAllocationException
     */
    public function delete(Allocation $allocation): void
    {
        if ($allocation->server_id !== null) {
            throw new ServerUsingAllocationException(trans('exceptions.allocations.server_using'));
        }

        $allocation->delete();
    }
}
