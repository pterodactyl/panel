<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Backups\DeletesBackups;
use Pterodactyl\Contracts\Backups\InitiatesBackups;
use Pterodactyl\Exceptions\Service\Backup\TooManyBackupsException;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;
use UnexpectedValueException;

final class InitiateBackup implements InitiatesBackups
{
    /** @var list<string> */
    private array $ignoredFiles = [];

    private bool $isLocked = false;

    public function __construct(
        private readonly DeletesBackups $deleteBackup,
        private readonly BackupManager $backupManager,
    ) {}

    /**
     * A backup's fields: an optional name, whether it is locked against deletion, and the
     * files and folders to leave out of it.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:191'],
            'is_locked' => ['nullable', 'boolean'],
            'ignored_files' => ['nullable', 'array', 'list'],
            'ignored_files.*' => ['string'],
        ];
    }

    /**
     * Validates backup input and returns the backup it describes. A name left out or blank
     * is null, and empty entries are dropped from the ignored files.
     *
     * @param  array<string, mixed>  $input
     * @return array{name: string|null, is_locked: bool, ignored_files: list<string>}
     */
    public static function attributes(array $input): array
    {
        $input = Validator::make($input, self::rules())->safe();

        // Don't use the PHP empty function here in case anything that is "empty" by default
        // (0, false, etc.) was passed as a file or folder name.
        $ignored = array_map(fn (int|string $key): string => $input->string('ignored_files.'.$key)->toString(), array_keys($input->array('ignored_files')));

        return [
            'name' => $input->filled('name') ? $input->string('name')->toString() : null,
            'is_locked' => $input->boolean('is_locked'),
            'ignored_files' => array_values(array_filter($ignored, fn (string $file): bool => $file !== '')),
        ];
    }

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
     * Sets the files to be ignored by this backup. They are validated when the backup is
     * initiated.
     *
     * @param  list<string>|null  $ignored
     */
    public function setIgnoredFiles(?array $ignored): InitiatesBackups
    {
        $this->ignoredFiles = $ignored ?? [];

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
     * @throws ValidationException
     */
    public function initiate(Server $server, ?string $name = null, bool $override = false): Backup
    {
        $attributes = self::attributes(['name' => $name, 'is_locked' => $this->isLocked, 'ignored_files' => $this->ignoredFiles]);

        [$backup, $rotated] = DB::transaction(/** @return array{Backup, Collection<int, Backup>} */ function () use ($server, $attributes, $override): array {
            $limit = config('backups.throttles.limit');
            $period = config('backups.throttles.period');
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
                'name' => mb_trim($attributes['name'] ?? '') ?: sprintf('Backup at %s', CarbonImmutable::now()->toDateTimeString()),
                'ignored_files' => $attributes['ignored_files'],
                'disk' => $this->backupManager->getDefaultAdapter(),
                'is_locked' => $attributes['is_locked'],
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

        Activity::event('server:backup.start')
            ->subject($server, $backup)
            ->property(['name' => $backup->name, 'locked' => $backup->is_locked])
            ->log();

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
