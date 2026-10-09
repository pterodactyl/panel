<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Backups;

use Pterodactyl\Contracts\Backups\TogglesBackupLocks;
use Pterodactyl\Models\Backup;

final class ToggleBackupLock implements TogglesBackupLocks
{
    public function toggle(Backup $backup): Backup
    {
        $backup->update(['is_locked' => ! $backup->is_locked]);

        return $backup;
    }
}
