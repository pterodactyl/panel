<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Backup;
use Throwable;

interface CompletesBackups
{
    /** @param BackupCompletionData $data
     * @throws DisplayException
     * @throws Throwable
     */
    public function complete(Backup $backup, array $data): void;
}
