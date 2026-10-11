<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Illuminate\Validation\ValidationException;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;

interface InitiatesBackups
{
    public function setIsLocked(bool $isLocked): self;

    /**
     * The files and folders to leave out of the backup. Empty entries are dropped.
     *
     * @param  list<string>|null  $ignored
     */
    public function setIgnoredFiles(?array $ignored): self;

    /**
     * Start a backup of the server on Wings. The name and the ignored files are validated
     * here, so they can come straight from any caller: a name is at most 191 characters,
     * and the ignored files are a list of strings, reported under `ignored_files`. A name
     * left out or blank becomes one with the time the backup started.
     *
     * @throws ValidationException
     */
    public function initiate(Server $server, ?string $name = null, bool $override = false): Backup;
}
