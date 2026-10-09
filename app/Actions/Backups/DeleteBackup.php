<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Backups;

use Illuminate\Http\Response;
use LogicException;
use Pterodactyl\Contracts\Backups\DeletesBackups;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Backup\BackupLockedException;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Backup;
use Throwable;

final readonly class DeleteBackup implements DeletesBackups
{
    public function __construct(
        private BackupManager $manager,
    ) {}

    /**
     * Deletes a backup from the system. If the backup is stored in S3 a request
     * will be made to delete that backup from the disk as well.
     *
     * @throws Throwable
     */
    public function delete(Backup $backup): void
    {
        // If the backup is marked as failed it can still be deleted, even if locked
        // since the UI doesn't allow you to unlock a failed backup in the first place.
        //
        // I also don't really see any reason you'd have a locked, failed backup to keep
        // around. The logic that updates the backup to the failed state will also remove
        // the lock, so this condition should really never happen.
        throw_if($backup->is_locked && ($backup->is_successful && ($backup->completed_at) !== null), BackupLockedException::class);

        if ($backup->disk === Backup::ADAPTER_AWS_S3) {
            $this->deleteFromS3($backup);

            return;
        }

        try {
            Daemon::server($backup->server)->backups()->delete($backup);
        } catch (DaemonConnectionException $daemonConnectionException) {
            // Don't fail the request if the Daemon responds with a 404, just assume the backup
            // doesn't actually exist and remove its reference from the Panel as well.
            throw_if($daemonConnectionException->getStatusCode() !== Response::HTTP_NOT_FOUND, $daemonConnectionException);
        }

        $backup->delete();
    }

    /**
     * Deletes a backup from an S3 disk. The object is removed first so the row is
     * kept if S3 rejects the request.
     *
     * @throws Throwable
     */
    private function deleteFromS3(Backup $backup): void
    {
        $adapter = $this->manager->adapter(Backup::ADAPTER_AWS_S3);
        throw_unless($adapter instanceof S3Filesystem, LogicException::class, 'The S3 backup adapter is not configured correctly.');

        $adapter->getClient()->deleteObject([
            'Bucket' => $adapter->getBucket(),
            'Key' => sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid),
        ]);

        $backup->delete();
    }
}
