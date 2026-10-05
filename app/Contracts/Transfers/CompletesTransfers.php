<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Transfers;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerTransfer;
use Throwable;

interface CompletesTransfers
{
    /**
     * Moves the server onto its new node and allocations, then removes it from the old node.
     *
     * @throws Throwable
     */
    public function complete(ServerTransfer $transfer): Server;
}
