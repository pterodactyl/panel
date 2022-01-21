<?php

namespace Pterodactyl\Services\Servers;

use Exception;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Backups\DeleteBackupService;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class ServerDeletionService
{
    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService
     */
    private $databaseManagementService;

    /**
     * @var \Pterodactyl\Services\Backups\DeleteBackupService
     */
    private $deleteBackupService;

    /**
     * DeletionService constructor.
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepository $daemonServerRepository,
        DatabaseManagementService $databaseManagementService,
        DeleteBackupService $deleteBackupService
    ) {
        $this->connection = $connection;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->databaseManagementService = $databaseManagementService;
        $this->deleteBackupService = $deleteBackupService;
    }

    /**
     * Set if the server should be forcibly deleted from the panel (ignoring daemon errors) or not.
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function withForce($bool = true)
    {
        $this->force = $bool;

        return $this;
    }

    /**
     * Delete a server from the panel and remove any associated databases from hosts.
     *
     * @throws \Throwable
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function handle(Server $server)
    {
        $this->connection->transaction(function () use ($server) {
            foreach ($server->backups as $backup) {
                // Unlock backup to prevent BackupLockedException
                $backup->update(['is_locked' => false]);
                try {
                    $this->deleteBackupService->handle($backup);
                } catch (Exception $exception) {
                    if (!$this->force) {
                        throw $exception;
                    }

                    // Oh well, just try to delete the backup entry we have from the database
                    // so that the server itself can be deleted. This will leave it dangling on
                    // the host instance, but we couldn't delete it anyways so not sure how we would
                    // handle this better anyways.
                    //
                    // @see https://github.com/pterodactyl/panel/issues/2085
                    $backup->delete();

                    Log::warning($exception);
                }
            }

            foreach ($server->databases as $database) {
                try {
                    $this->databaseManagementService->delete($database);
                } catch (Exception $exception) {
                    if (!$this->force) {
                        throw $exception;
                    }

                    // Oh well, just try to delete the database entry we have from the database
                    // so that the server itself can be deleted. This will leave it dangling on
                    // the host instance, but we couldn't delete it anyways so not sure how we would
                    // handle this better anyways.
                    //
                    // @see https://github.com/pterodactyl/panel/issues/2085
                    $database->delete();

                    Log::warning($exception);
                }
            }

            $server->delete();
        });
    }
}
