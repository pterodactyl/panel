<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;

interface InitiatesBackups
{
    public function setIsLocked(bool $isLocked): self;

    /** @param list<string>|null $ignored */
    public function setIgnoredFiles(?array $ignored): self;

    public function initiate(Server $server, ?string $name = null, bool $override = false): Backup;
}
