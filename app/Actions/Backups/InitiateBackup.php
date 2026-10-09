<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
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
     * The rows are written in a transaction, but Wings is only asked to start the backup
     * once that transaction commits, and backups rotated out to make room are deleted only
     * after Wings accepted the new one. A rejected backup is marked failed and nothing is
     * rotated out, so the oldest backup survives a failed attempt.
     *
     * @throws Throwable
     * @throws TooManyBackupsException
     * @throws TooManyRequestsHttpException
     */
    public function initiate(Server $server, ?string $name = null, bool $override = false): Backup
    {
        [$backup, $rotated] = DB::transaction(/** @return array{Backup, Collection<int, Backup>} */ function () use ($server, $name, $override): array {
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

            $rotated = new Collection;
            $count = $server->backups()->nonFailed()->lockForUpdate()->count();
            if (! $server->backup_limit || $count >= $server->backup_limit) {
                // Do not allow the user to continue if this server is already at its limit and can't override.
                if (! $override || $server->backup_limit <= 0) {
                    throw new TooManyBackupsException($server->backup_limit);
                }

                // Pick the oldest backups that are not "locked" (indicating a backup that should never
                // be automatically purged) to make room for this one. A backup rotated out by another
                // request that has not finished deleting it still counts here, so take enough to bring
                // the server back to its limit. They are deleted once Wings accepts the new backup.
                $needed = $count - $server->backup_limit + 1;
                $rotated = $server->backups()
                    ->nonFailed()
                    ->where('is_locked', false)
                    ->orderBy('created_at')
                    ->limit($needed)
                    ->get();
                if ($rotated->count() < $needed) {
                    throw new TooManyBackupsException($server->backup_limit);
                }
            }

            $backup = $server->backups()->create([
                'uuid' => Uuid::uuid4()->toString(),
                'name' => mb_trim($name ?? '') ?: sprintf('Backup at %s', CarbonImmutable::now()->toDateTimeString()),
                'ignored_files' => array_values($this->ignoredFiles),
                'disk' => $this->backupManager->getDefaultAdapter(),
                'is_locked' => $this->isLocked,
            ]);

            return [$backup, $rotated];
        });

        try {
            Daemon::server($server)->backups()->create($backup);
        } catch (Throwable $throwable) {
            // Record the attempt as failed, the same state Wings reports for a failed backup.
            $backup->forceFill([
                'is_successful' => false,
                'is_locked' => false,
                'completed_at' => CarbonImmutable::now(),
            ])->save();

            throw $throwable;
        }

        foreach ($rotated as $old) {
            // Re-read the row: it may have been deleted or locked since it was picked.
            $current = Backup::query()->whereKey($old->getKey())->first();
            if ($current === null) {
                continue;
            }

            try {
                $this->deleteBackup->delete($current);
            } catch (Throwable $throwable) {
                // The new backup is already running, so leave the server one over its limit
                // rather than failing a request Wings accepted.
                report($throwable);
            }
        }

        return $backup->refresh();
    }
}
