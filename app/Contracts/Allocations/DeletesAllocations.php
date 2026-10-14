<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Allocations;

use Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException;
use Pterodactyl\Models\Allocation;

interface DeletesAllocations
{
    /**
     * An allocation a server is using is not deleted.
     *
     * @throws ServerUsingAllocationException
     */
    public function delete(Allocation $allocation): void;
}
