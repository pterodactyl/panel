<?php

namespace Pterodactyl\Services\Backups;

use Ramsey\Uuid\Uuid;
use Carbon\CarbonImmutable;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Pterodactyl\Repositories\Wings\DaemonBackupRepository;

class InitiateBackupService
{
    /**
     * @var string|null
     */
    private $ignoredFiles;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\BackupRepository
     */
    private $repository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonBackupRepository
     */
    private $daemonBackupRepository;

    /**
     * InitiateBackupService constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\BackupRepository $repository
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Repositories\Wings\DaemonBackupRepository $daemonBackupRepository
     */
    public function __construct(
        BackupRepository $repository,
        ConnectionInterface $connection,
        DaemonBackupRepository $daemonBackupRepository
    ) {
        $this->repository = $repository;
        $this->connection = $connection;
        $this->daemonBackupRepository = $daemonBackupRepository;
    }

    /**
     * Sets the files to be ignored by this backup.
     *
     * @param string|null $ignored
     * @return $this
     */
    public function setIgnoredFiles(?string $ignored)
    {
        $this->ignoredFiles = $ignored;

        return $this;
    }

    /**
     * Initiates the backup process for a server on the daemon.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param string|null $name
     * @return \Pterodactyl\Models\Backup
     *
     * @throws \Throwable
     */
    public function handle(Server $server, string $name = null): Backup
    {
        return $this->connection->transaction(function () use ($server, $name) {
            /** @var \Pterodactyl\Models\Backup $backup */
            $backup = $this->repository->create([
                'server_id' => $server->id,
                'uuid' => Uuid::uuid4()->toString(),
                'name' => trim($name) ?: sprintf('Backup at %s', CarbonImmutable::now()->toDateTimeString()),
                'ignored_files' => $this->ignoredFiles ?? '',
                'disk' => 'local',
            ], true, true);

            $this->daemonBackupRepository->setServer($server)->backup($backup);

            return $backup;
        });
    }
}
