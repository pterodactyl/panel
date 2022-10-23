<?php

namespace Pterodactyl\Services\Allocations;

use Pterodactyl\Models\Allocation;
use Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException;

class AllocationDeletionService
{
    /**
     * Delete an allocation from the database only if it does not have a server
     * that is actively attached to it.
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function handle(Allocation $allocation): int
    {
        if (!is_null($allocation->server_id)) {
            throw new ServerUsingAllocationException(trans('exceptions.allocations.server_using'));
        }

        return $allocation->delete();
    }
}
