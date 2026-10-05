<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Node;

interface RestoresBackupStatuses
{
    public function restoreStatus(string $backupUuid, Node $node): Backup;
}
