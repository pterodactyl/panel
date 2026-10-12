<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Backups;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Backups\GeneratesBackupDownloadLinks;
use Pterodactyl\Contracts\Backups\RestoresBackups;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class RestoreBackup implements RestoresBackups
{
    public function __construct(
        private GeneratesBackupDownloadLinks $downloadLink,
    ) {}

    /**
     * The action takes `truncate` as a bool, which always meets these rules.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'truncate' => ['required', 'boolean'],
        ];
    }

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

        // ResetServerStates finds the backup a restoring server was given through this entry.
        Activity::event('server:backup.restore')
            ->subject($server, $backup)
            ->property(['name' => $backup->name, 'truncate' => $truncate])
            ->log();
    }
}
