<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Pterodactyl\Models\Backup;
use Throwable;

interface DeletesBackups
{
    /** @throws Throwable */
    public function delete(Backup $backup): void;
}
