<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Pterodactyl\Models\Backup;
use Throwable;

interface DeletesBackups
{
    /**
     * An S3 backup's archive is deleted from its bucket as well.
     *
     * @throws Throwable
     */
    public function delete(Backup $backup): void;
}
