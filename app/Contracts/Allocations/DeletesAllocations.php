<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Allocations;

use Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException;
use Pterodactyl\Models\Allocation;

interface DeletesAllocations
{
    /**
     * Delete an allocation, provided no server is attached to it.
     *
     * @throws ServerUsingAllocationException
     */
    public function delete(Allocation $allocation): void;
}
