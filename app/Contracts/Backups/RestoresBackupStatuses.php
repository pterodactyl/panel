<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Node;

interface RestoresBackupStatuses
{
    /**
     * Return a server to a normal state once Wings reports how restoring one of its
     * backups ended. A restore that failed is recoverable by retrying it or reinstalling.
     */
    public function restoreStatus(string $backupUuid, Node $node, bool $successful): Backup;
}
