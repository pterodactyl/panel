<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\Backups\DeleteBackupService;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Pterodactyl\Services\Backups\InitiateBackupService;
use Pterodactyl\Transformers\Api\Client\BackupTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\GetBackupsRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\StoreBackupRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\DeleteBackupRequest;

class BackupController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Services\Backups\InitiateBackupService
     */
    private $initiateBackupService;

    /**
     * @var \Pterodactyl\Services\Backups\DeleteBackupService
     */
    private $deleteBackupService;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\BackupRepository
     */
    private $repository;

    /**
     * BackupController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\BackupRepository $repository
     * @param \Pterodactyl\Services\Backups\DeleteBackupService $deleteBackupService
     * @param \Pterodactyl\Services\Backups\InitiateBackupService $initiateBackupService
     */
    public function __construct(
        BackupRepository $repository,
        DeleteBackupService $deleteBackupService,
        InitiateBackupService $initiateBackupService
    ) {
        parent::__construct();

        $this->initiateBackupService = $initiateBackupService;
        $this->deleteBackupService = $deleteBackupService;
        $this->repository = $repository;
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
        $limit = min($request->query('per_page') ?? 20, 50);

        return $this->fractal->collection($server->backups()->paginate($limit))
            ->transformWith($this->getTransformer(BackupTransformer::class))
            ->toArray();
    }

    /**
     * Starts the backup process for a server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Backups\StoreBackupRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Exception|\Throwable
     */
    public function store(StoreBackupRequest $request, Server $server)
    {
        /** @var \Pterodactyl\Models\Backup $backup */
        $backup = $server->audit(AuditLog::ACTION_SERVER_BACKUP_STARTED, function (AuditLog $model, Server $server) use ($request) {
            $backup = $this->initiateBackupService
                ->setIgnoredFiles(
                    explode(PHP_EOL, $request->input('ignored') ?? '')
                )
                ->handle($server, $request->input('name'));

            $model->metadata = ['backup_uuid' => $backup->uuid];

            return $backup;
        });

        return $this->fractal->item($backup)
            ->transformWith($this->getTransformer(BackupTransformer::class))
            ->toArray();
    }

    /**
     * Returns information about a single backup.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Backups\GetBackupsRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Backup $backup
     * @return array
     */
    public function view(GetBackupsRequest $request, Server $server, Backup $backup)
    {
        return $this->fractal->item($backup)
            ->transformWith($this->getTransformer(BackupTransformer::class))
            ->toArray();
    }

    /**
     * Deletes a backup from the panel as well as the remote source where it is currently
     * being stored.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Backups\DeleteBackupRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Backup $backup
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function delete(DeleteBackupRequest $request, Server $server, Backup $backup)
    {
        $server->audit(AuditLog::ACTION_SERVER_BACKUP_DELETED, function () use ($backup) {
            $this->deleteBackupService->handle($backup);
        });

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
