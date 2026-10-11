<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Transfers;

use Illuminate\Validation\ValidationException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerTransfer;
use Throwable;

interface InitiatesTransfers
{
    /**
     * Records the transfer, reserves the target allocations, and tells the destination
     * node to begin pulling the server.
     *
     * The node and allocations are validated here, so they can come straight from any
     * caller: the node must not be the server's own, the default allocation must be no
     * server's default, and every allocation must exist, be unassigned and be on the node.
     * Errors are reported under node_id, allocation_id and allocation_additional.N.
     *
     * @param  list<int>  $additionalAllocations
     *
     * @throws DisplayException
     * @throws ValidationException
     * @throws Throwable
     */
    public function initiate(Server $server, Node $node, int $allocationId, array $additionalAllocations): ServerTransfer;
}
