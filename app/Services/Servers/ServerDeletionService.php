<?php

namespace Pterodactyl\Services\Servers;

use Exception;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Jobs\Backup\DeleteBackupJob;
use Pterodactyl\Jobs\Server\DeleteServerJob;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Services\Databases\DatabaseManagementService;

class ServerDeletionService
{
    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    private $dispatcher;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService
     */
    private $databaseManagementService;

    /**
     * DeletionService constructor.
     */
    public function __construct(
        Dispatcher $dispatcher,
        ConnectionInterface $connection,
        ServerRepository $repository,
        DatabaseManagementService $databaseManagementService
    ) {
        $this->dispatcher = $dispatcher;
        $this->connection = $connection;
        $this->repository = $repository;
        $this->databaseManagementService = $databaseManagementService;
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
        $this->repository->update($server->id, ['status' => 'deleting'], true, true);

        $this->connection->transaction(function () use ($server) {
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

            foreach ($server->backups as $backup) {
                // Unlock backup to prevent BackupLockedException
                $backup->update(['is_locked' => false]);
                try {
                    $job = new DeleteBackupJob($backup);
                    $this->dispatcher->dispatch($job);
                } catch (Exception $exception) {
                    if (!$this->force) {
                        throw $exception;
                    }

                    // Oh well, just try to delete the backup entry we have from the database
                    // so that the server itself can be deleted. This will leave it dangling on
                    // the host instance, but we couldn't delete it anyways so not sure how we would
                    // handle this better anyways.
                    $backup->delete();

                    Log::warning($exception);
                }
            }

            try {
                $job = new DeleteServerJob($server);
                $this->dispatcher->dispatch($job);
            } catch (Exception $exception) {
                // If there is an error not caused a 404 error and this isn't a forced delete,
                // go ahead and bail out. We specifically ignore a 404 since that can be assumed
                // to be a safe error, meaning the server doesn't exist at all on Wings so there
                // is no reason we need to bail out from that.
                if ($exception->getStatusCode() !== Response::HTTP_NOT_FOUND) {
                    if (!$this->force) {
                        throw $exception;
                    } else {
                        $server->delete();
                    }
                } else {
                    $server->delete();
                }

                Log::warning($exception);
            }
        });
    }
}
