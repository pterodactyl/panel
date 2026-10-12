<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Illuminate\Validation\ValidationException;
use Pterodactyl\Exceptions\Service\Backup\TooManyBackupsException;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

interface InitiatesBackups
{
    /** A locked backup cannot be deleted by users or by automated processes. */
    public function setIsLocked(bool $isLocked): self;

    /**
     * The files and folders to leave out of the backup. Empty entries are dropped.
     *
     * @param  list<string>|null  $ignored
     */
    public function setIgnoredFiles(?array $ignored): self;

    /**
     * Start a backup of the server on Wings. The name and the ignored files are validated here:
     * a name is at most 191 characters, and the ignored files are a list of strings, reported
     * under `ignored_files`. A name left out or blank becomes one with the time the backup
     * started.
     *
     * With $override, a server at its backup limit rotates out its oldest unlocked backups once
     * Wings accepts the new one. A backup Wings rejects is marked failed and nothing is rotated
     * out.
     *
     * @throws TooManyBackupsException
     * @throws TooManyRequestsHttpException
     * @throws ValidationException
     * @throws Throwable
     */
    public function initiate(Server $server, ?string $name = null, bool $override = false): Backup;
}
