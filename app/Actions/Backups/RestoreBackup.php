<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Backups;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Backups\GeneratesBackupDownloadLinks;
use Pterodactyl\Contracts\Backups\RestoresBackups;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

final readonly class RestoreBackup implements RestoresBackups
{
    public function __construct(
        private GeneratesBackupDownloadLinks $downloadLink,
    ) {}

    /**
     * Marks the server as restoring and asks Wings to unpack the backup over its files.
     * S3 backups are handed to Wings as a signed download link.
     *
     * @throws Throwable
     */
    public function restore(Server $server, Backup $backup, User $user, bool $truncate): void
    {
        throw_if(($server->status) !== null, BadRequestHttpException::class, 'This server is not currently in a state that allows for a backup to be restored.');
        throw_if(! $backup->is_successful || ($backup->completed_at) === null, BadRequestHttpException::class, 'This backup cannot be restored at this time: not completed or failed.');

        DB::transaction(function () use ($server, $backup, $user, $truncate): void {
            $url = $backup->disk === Backup::ADAPTER_AWS_S3
                ? $this->downloadLink->generate($backup, $user)
                : null;

            $server->update(['status' => Server::STATUS_RESTORING_BACKUP]);

            Daemon::server($server)->backups()->restore($backup, $url, $truncate);
        });
    }
}
