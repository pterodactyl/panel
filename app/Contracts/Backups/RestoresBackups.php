<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Throwable;

interface RestoresBackups
{
    /** @throws Throwable */
    public function restore(Server $server, Backup $backup, User $user, bool $truncate): void;
}
