<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Pterodactyl\Models\Backup;

interface TogglesBackupLocks
{
    public function toggle(Backup $backup): Backup;
}
