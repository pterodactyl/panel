<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Allocations;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Service\Allocation\CidrOutOfRangeException;
use Pterodactyl\Exceptions\Service\Allocation\InvalidPortMappingException;
use Pterodactyl\Exceptions\Service\Allocation\PortOutOfRangeException;
use Pterodactyl\Exceptions\Service\Allocation\TooManyPortsInRangeException;
use Pterodactyl\Models\Node;

interface CreatesAllocations
{
    /**
     * @param  AllocationAssignmentData  $data
     *
     * @throws DisplayException
     * @throws CidrOutOfRangeException
     * @throws InvalidPortMappingException
     * @throws PortOutOfRangeException
     * @throws TooManyPortsInRangeException
     */
    public function create(Node $node, array $data): void;
}
