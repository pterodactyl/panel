<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Throwable;

interface RestoresBackups
{
    /**
     * Mark the server as restoring and ask Wings to unpack the backup over its files, deleting
     * them first when $truncate is set.
     *
     * @throws Throwable
     */
    public function restore(Server $server, Backup $backup, User $user, bool $truncate): void;
}
