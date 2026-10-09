<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Contracts\Databases\DeletesDatabases;
use Pterodactyl\Contracts\Servers\DeletesServers;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;
use Throwable;

final class DeleteServer implements DeletesServers
{
    private bool $force = false;

    public function __construct(
        private readonly DeletesDatabases $deleteDatabase,
    ) {}

    public function withForce(bool $bool = true): self
    {
        $this->force = $bool;

        return $this;
    }

    /**
     * @throws Throwable
     * @throws DisplayException
     */
    public function delete(Server $server): void
    {
        try {
            Daemon::server($server)->delete();
        } catch (DaemonConnectionException $daemonConnectionException) {
            // A missing Wings record is safe to treat as already deleted. Force mode also
            // tolerates other Wings failures.
            throw_if(! $this->force && $daemonConnectionException->getStatusCode() !== Response::HTTP_NOT_FOUND, $daemonConnectionException);
            Log::warning($daemonConnectionException->getMessage(), ['exception' => $daemonConnectionException]);
        }

        // The remote databases are dropped only after the Panel records are gone. Dropping
        // them inside the transaction could not be undone if the deletion later rolled back.
        $databases = DB::transaction(function () use ($server): Collection {
            $databases = $server->loadMissing('databases.host')->databases;
            foreach ($databases as $database) {
                $database->delete();
            }

            // Clear notes before releasing the allocations back to the unassigned pool.
            $server->allocations()->update(['notes' => null]);
            $server->delete();

            return $databases;
        });

        foreach ($databases as $database) {
            try {
                $this->deleteDatabase->delete($database);
            } catch (Throwable $throwable) {
                // The server is already deleted, so report the orphaned database for manual
                // cleanup and keep dropping the rest.
                report($throwable);
            }
        }

        // The row is gone, so only the uuid travels. Dispatched after any outer transaction commits.
        Event::dispatch(new OperationCompleted($server->uuid, 'delete', true, $server->uuid));
    }
}
