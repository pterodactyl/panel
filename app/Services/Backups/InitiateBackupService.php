<?php

namespace Pterodactyl\Services\Backups;

use Ramsey\Uuid\Uuid;
use Carbon\CarbonImmutable;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\BackupRepository;

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
     * InitiateBackupService constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\BackupRepository $repository
     */
    public function __construct(BackupRepository $repository)
    {
        $this->repository = $repository;
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
     * @throws \Exception
     */
    public function handle(Server $server, string $name = null): Backup
    {
        /** @var \Pterodactyl\Models\Backup $backup */
        $backup = $this->repository->create([
            'server_id' => $server->id,
            'uuid' => Uuid::uuid4()->toString(),
            'name' => trim($name) ?: sprintf('Backup at %s', CarbonImmutable::now()->toDateTimeString()),
            'ignored_files' => $this->ignoredFiles ?? '',
            'disk' => 'local',
        ], true, true);

        return $backup;
    }
}
