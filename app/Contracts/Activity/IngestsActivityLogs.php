<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Activity;

use Pterodactyl\Models\Node;

interface IngestsActivityLogs
{
    /** @param ActivityEvents $events */
    public function ingest(Node $node, array $events): void;
}
