<?php

namespace Pterodactyl\Services\Backups;

use Pterodactyl\Models\Backup;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Pterodactyl\Repositories\Wings\DaemonBackupRepository;

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
     * DeleteBackupService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Repositories\Eloquent\BackupRepository $repository
     * @param \Pterodactyl\Repositories\Wings\DaemonBackupRepository $daemonBackupRepository
     */
    public function __construct(
        ConnectionInterface $connection,
        BackupRepository $repository,
        DaemonBackupRepository $daemonBackupRepository
    ) {
        $this->repository = $repository;
        $this->daemonBackupRepository = $daemonBackupRepository;
        $this->connection = $connection;
    }

    /**
     * Deletes a backup from the system.
     *
     * @param \Pterodactyl\Models\Backup $backup
     * @throws \Throwable
     */
    public function handle(Backup $backup)
    {
        $this->connection->transaction(function () use ($backup) {
            $this->daemonBackupRepository->setServer($backup->server)->delete($backup);

            $this->repository->delete($backup->id);
        });
    }
}
