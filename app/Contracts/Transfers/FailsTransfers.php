<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Transfers;

use Pterodactyl\Models\ServerTransfer;
use Throwable;

interface FailsTransfers
{
    /**
     * Marks the transfer as failed and releases the allocations reserved on the target node.
     *
     * @throws Throwable
     */
    public function fail(ServerTransfer $transfer): void;
}
