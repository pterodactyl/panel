<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Nodes;

use Pterodactyl\Models\Node;
use Throwable;

interface ResetsServerStates
{
    /**
     * Returns every installing or restoring server on the node to a normal state after a
     * Wings restart, logging a failed restore for each backup that was in progress.
     *
     * @throws Throwable
     */
    public function reset(Node $node): void;
}
