<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Backups\DeletesBackups;
use Pterodactyl\Contracts\Backups\InitiatesBackups;
use Pterodactyl\Exceptions\Service\Backup\TooManyBackupsException;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\JsonValueGuard;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;
use UnexpectedValueException;

final class InitiateBackup implements InitiatesBackups
{
    /** @var array<int, string> */
    private array $ignoredFiles = [];

    private bool $isLocked = false;

    public function __construct(
        private readonly DeletesBackups $deleteBackup,
        private readonly BackupManager $backupManager,
    ) {}

    /**
     * Set if the backup should be locked once it is created which will prevent
     * its deletion by users or automated system processes.
     */
    public function setIsLocked(bool $isLocked): InitiatesBackups
    {
        $this->isLocked = $isLocked;

        return $this;
    }

    /**
     * Sets the files to be ignored by this backup.
     *
     * @param  list<string>|null  $ignored
     */
    public function setIgnoredFiles(?array $ignored): InitiatesBackups
    {
        // Set the ignored files to be any values that are not empty in the array. Don't use
        // the PHP empty function here incase anything that is "empty" by default (0, false, etc.)
        // were passed as a file or folder name.
        $this->ignoredFiles = ($ignored) === null ? [] : array_filter($ignored, fn (string $value): bool => $value !== '');

        return $this;
    }

    /**
     * Initiates the backup process for a server on Wings.
     *
     * @throws Throwable
     * @throws TooManyBackupsException
     * @throws TooManyRequestsHttpException
     */
    public function initiate(Server $server, ?string $name = null, bool $override = false): Backup
    {
        return DB::transaction(function () use ($server, $name, $override): Backup {
            $limit = JsonValueGuard::integer(config('backups.throttles.limit'));
            $period = JsonValueGuard::integer(config('backups.throttles.period'));
            if ($period > 0) {
                $previousCount = $server->backups()
                    ->withTrashed()
                    ->nonFailed()
                    ->where('created_at', '>=', CarbonImmutable::now()->subSeconds($period))
                    ->lockForUpdate()
                    ->count();
                if ($previousCount >= $limit) {
                    $message = sprintf('Only %d backups may be generated within a %d second span of time.', $limit, $period);
                    $latest = $server->backups()
                        ->withTrashed()
                        ->nonFailed()
                        ->where('created_at', '>=', CarbonImmutable::now()->subSeconds($period))
                        ->orderByDesc('created_at')
                        ->first() ?? throw new UnexpectedValueException('The backup throttle query returned an empty result set.');

                    // SAFETY: HTTP Retry-After uses whole seconds, so truncating Carbon's fractional difference is intentional.
                    throw new TooManyRequestsHttpException((int) CarbonImmutable::now()->diffInSeconds($latest->created_at->addSeconds($period)), $message);
                }
            }

            $successful = $server->backups()->nonFailed();
            if (! $server->backup_limit || $successful->lockForUpdate()->count() >= $server->backup_limit) {
                // Do not allow the user to continue if this server is already at its limit and can't override.
                if (! $override || $server->backup_limit <= 0) {
                    throw new TooManyBackupsException($server->backup_limit);
                }

                // Get the oldest backup the server has that is not "locked" (indicating a backup that should
                // never be automatically purged). If we find a backup we will delete it and then continue with
                // this process. If no backup is found that can be used an exception is thrown.
                $oldest = $successful->where('is_locked', false)->orderBy('created_at')->first();
                if (! $oldest) {
                    throw new TooManyBackupsException($server->backup_limit);
                }

                $this->deleteBackup->delete($oldest);
            }

            $backup = $server->backups()->create([
                'uuid' => Uuid::uuid4()->toString(),
                'name' => mb_trim($name ?? '') ?: sprintf('Backup at %s', CarbonImmutable::now()->toDateTimeString()),
                'ignored_files' => array_values($this->ignoredFiles),
                'disk' => $this->backupManager->getDefaultAdapter(),
                'is_locked' => $this->isLocked,
            ]);

            Daemon::server($server)->backups()->create($backup);

            return $backup->refresh();
        });
    }
}
