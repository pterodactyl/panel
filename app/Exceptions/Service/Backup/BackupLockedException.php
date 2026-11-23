<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Service\Backup;

use Pterodactyl\Exceptions\DisplayException;

class BackupLockedException extends DisplayException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.backups.locked'));
    }
}
