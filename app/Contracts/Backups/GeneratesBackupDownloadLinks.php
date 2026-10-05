<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Pterodactyl\Models\Backup;
use Pterodactyl\Models\User;

interface GeneratesBackupDownloadLinks
{
    public function generate(Backup $backup, User $user): string;
}
