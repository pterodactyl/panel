<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Client\BackupTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\GetBackupsRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\StoreBackupRequest;

class BackupController extends ClientApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns all of the backups for a given server instance in a paginated
     * result set.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Backups\GetBackupsRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     */
    public function index(GetBackupsRequest $request, Server $server)
    {
        return $this->fractal->collection($server->backups()->paginate(20))
            ->transformWith($this->getTransformer(BackupTransformer::class))
            ->toArray();
    }

    /**
     * Starts the backup process for a server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Backups\StoreBackupRequest $request
     * @param \Pterodactyl\Models\Server $server
     */
    public function store(StoreBackupRequest $request, Server $server)
    {
    }

    public function view()
    {
    }

    public function update()
    {
    }

    public function delete()
    {
    }
}
