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
     * The input is validated here. A port that cannot be read, falls outside 1025-65535 or opens
     * a range of more than 1000 ports is refused with the exceptions below.
     *
     * @param  array<string, mixed>  $input  allocation_ip (an IP address, CIDR range or hostname), allocation_ports (port numbers, or ranges such as "25565-25570") and optionally allocation_alias
     *
     * @throws DisplayException
     * @throws CidrOutOfRangeException
     * @throws InvalidPortMappingException
     * @throws PortOutOfRangeException
     * @throws TooManyPortsInRangeException
     */
    public function create(Node $node, array $input): void;
}
