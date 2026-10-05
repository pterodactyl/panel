<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Transfers;

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
     * @param  list<int>  $additionalAllocations
     *
     * @throws DisplayException
     * @throws Throwable
     */
    public function initiate(Server $server, Node $node, int $allocationId, array $additionalAllocations): ServerTransfer;
}
