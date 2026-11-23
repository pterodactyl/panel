<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Service\Backup;

use Pterodactyl\Exceptions\DisplayException;

class TooManyBackupsException extends DisplayException
{
    /**
     * TooManyBackupsException constructor.
     */
    public function __construct(int $backupLimit)
    {
        parent::__construct(
            __('exceptions.backups.limit_reached', ['limit' => $backupLimit])
        );
    }
}
