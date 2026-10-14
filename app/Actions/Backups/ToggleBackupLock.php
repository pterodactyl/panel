<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Backups;

use Pterodactyl\Contracts\Backups\TogglesBackupLocks;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Backup;

final class ToggleBackupLock implements TogglesBackupLocks
{
    public function toggle(Backup $backup): Backup
    {
        $backup->update(['is_locked' => ! $backup->is_locked]);

        Activity::event($backup->is_locked ? 'server:backup.lock' : 'server:backup.unlock')
            ->subject($backup->server, $backup)
            ->property('name', $backup->name)
            ->log();

        return $backup;
    }
}
