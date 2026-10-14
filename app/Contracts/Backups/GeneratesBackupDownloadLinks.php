<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Pterodactyl\Models\Backup;
use Pterodactyl\Models\User;

interface GeneratesBackupDownloadLinks
{
    /**
     * A short-lived link the user can download the backup from: a presigned S3 URL, or a signed
     * link to the backup on its node.
     */
    public function generate(Backup $backup, User $user): string;
}
