<?php

namespace Pterodactyl\Services\Backups;

use Illuminate\Http\Response;
use Pterodactyl\Models\Backup;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Pterodactyl\Repositories\Wings\DaemonBackupRepository;
use Pterodactyl\Exceptions\Service\Backup\BackupLockedException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class DeleteBackupService
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\BackupRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonBackupRepository
     */
    private $daemonBackupRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Extensions\Backups\BackupManager
     */
    private $manager;

    /**
     * DeleteBackupService constructor.
     */
    public function __construct(
        ConnectionInterface $connection,
        BackupRepository $repository,
        BackupManager $manager,
        DaemonBackupRepository $daemonBackupRepository
    ) {
        $this->repository = $repository;
        $this->daemonBackupRepository = $daemonBackupRepository;
        $this->connection = $connection;
        $this->manager = $manager;
    }

    /**
     * Deletes a backup from the system.
     *
     * @throws \Throwable
     */
    public function handle(Backup $backup)
    {
        if ($backup->is_locked) {
            throw new BackupLockedException();
        }

        if ($backup->disk === Backup::ADAPTER_AWS_S3) {
            $this->deleteFromS3($backup);

            return;
        }

        $this->connection->transaction(function () use ($backup) {
            try {
                $this->daemonBackupRepository->setServer($backup->server)->delete($backup);
            } catch (DaemonConnectionException $exception) {
                $previous = $exception->getPrevious();
                // Don't fail the request if the Daemon responds with a 404, just assume the backup
                // doesn't actually exist and remove it's reference from the Panel as well.
                if (!$previous instanceof ClientException || $previous->getResponse()->getStatusCode() !== Response::HTTP_NOT_FOUND) {
                    throw $exception;
                }
            }

            $this->repository->delete($backup->id);
        });
    }

    /**
     * Deletes a backup from an S3 disk.
     *
     * @throws \Throwable
     */
    protected function deleteFromS3(Backup $backup)
    {
        $this->connection->transaction(function () use ($backup) {
            $this->repository->delete($backup->id);

            /** @var \League\Flysystem\AwsS3v3\AwsS3Adapter $adapter */
            $adapter = $this->manager->adapter(Backup::ADAPTER_AWS_S3);

            $adapter->getClient()->deleteObject([
                'Bucket' => $adapter->getBucket(),
                'Key' => sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid),
            ]);
        });
    }
}
