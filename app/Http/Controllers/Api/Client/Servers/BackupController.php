<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Backups\DeletesBackups;
use Pterodactyl\Contracts\Backups\GeneratesBackupDownloadLinks;
use Pterodactyl\Contracts\Backups\InitiatesBackups;
use Pterodactyl\Contracts\Backups\RestoresBackups;
use Pterodactyl\Contracts\Backups\TogglesBackupLocks;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\DeleteBackupRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\DownloadBackupRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\ListBackupsRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\RestoreBackupRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\StoreBackupRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\ToggleBackupLockRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\ViewBackupRequest;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Client\BackupTransformer;
use Spatie\Fractalistic\Exceptions\InvalidTransformation;
use Spatie\Fractalistic\Exceptions\NoTransformerSpecified;
use Throwable;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Backups', 'Create, inspect, download, restore, lock, and delete server backups.')]
class BackupController extends ClientApiController
{
    private const array SIGNED_URL_EXAMPLE = [
        'object' => 'signed_url',
        'attributes' => [
            'url' => 'https://node.example.test/download/backup?token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.example.signature',
        ],
    ];

    private const array BAD_REQUEST_ERROR = [
        'errors' => [
            [
                'code' => 'BadRequestHttpException',
                'status' => '400',
                'detail' => 'This backup cannot be restored at this time: not completed or failed.',
            ],
        ],
    ];

    /**
     * Returns all the backups for a given server instance in a paginated
     * result set.
     *
     *
     * @return ApiPayload
     */
    #[Endpoint('List server backups', 'Returns a paginated list of backups for the server.')]
    #[QueryParam('page', 'integer', 'The page number to return.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Number of backups to return per page. The maximum is 50.', required: false, example: 20)]
    #[ResponseFromTransformer(BackupTransformer::class, Backup::class, description: 'Server backups returned.', collection: true, resourceKey: 'backup', paginate: [IlluminatePaginatorAdapter::class, 20], meta: ['backup_count' => 1])]
    public function index(ListBackupsRequest $request, Server $server): array
    {
        $validated = $request->payload();

        return Fractal::collection($server->backups()->paginate($validated['per_page']))
            ->transformWith($this->getTransformer(BackupTransformer::class))
            ->addMeta([
                'backup_count' => $server->backups()->nonFailed()->count(),
            ])
            ->toResponseArray();
    }

    /**
     * Starts the backup process for a server.
     *
     *
     * @return ApiPayload
     *
     * @throws InvalidTransformation
     * @throws NoTransformerSpecified
     * @throws Throwable
     */
    #[Endpoint('Create server backup', 'Starts a new backup for the server.')]
    #[ResponseFromTransformer(BackupTransformer::class, Backup::class, description: 'Backup started.', resourceKey: 'backup')]
    public function store(StoreBackupRequest $request, InitiatesBackups $initiate, Server $server): array
    {
        $user = $this->authenticatedUser($request);
        $validated = $request->payload();
        $action = $initiate
            ->setIgnoredFiles(explode(PHP_EOL, $validated['ignored'] ?? ''));

        // Only set the lock status if the user even has permission to delete backups,
        // otherwise ignore this status. This gets a little funky since it isn't clear
        // how best to allow a user to create a backup that is locked without also preventing
        // them from just filling up a server with backups that can never be deleted?
        if ($user->can(Permissions::BackupDelete->value, $server)) {
            $action->setIsLocked($validated['is_locked']);
        }

        $backup = $action->initiate($server, $validated['name']);

        Activity::event('server:backup.start')->subject($backup)->property([
            'name' => $backup->name,
            'locked' => $backup->is_locked,
        ])->log();

        return Fractal::item($backup)
            ->transformWith($this->getTransformer(BackupTransformer::class))
            ->toResponseArray();
    }

    /**
     * Toggles the lock status of a given backup for a server.
     *
     *
     * @return ApiPayload
     *
     * @throws Throwable
     */
    #[Endpoint('Toggle backup lock', 'Toggles whether a backup is locked against deletion.')]
    #[ResponseFromTransformer(BackupTransformer::class, Backup::class, description: 'Backup lock state toggled.', resourceKey: 'backup')]
    public function toggleLock(ToggleBackupLockRequest $request, TogglesBackupLocks $toggle, Server $server, Backup $backup): array
    {
        $backup = $toggle->toggle($backup);

        $action = $backup->is_locked ? 'server:backup.lock' : 'server:backup.unlock';

        Activity::event($action)->subject($backup)->property('name', $backup->name)->log();

        return Fractal::item($backup)
            ->transformWith($this->getTransformer(BackupTransformer::class))
            ->toResponseArray();
    }

    /**
     * Returns information about a single backup.
     *
     *
     * @return ApiPayload
     */
    #[Endpoint('Get server backup', 'Returns information about one server backup.')]
    #[ResponseFromTransformer(BackupTransformer::class, Backup::class, description: 'Server backup returned.', resourceKey: 'backup')]
    public function view(ViewBackupRequest $request, Server $server, Backup $backup): array
    {
        return Fractal::item($backup)
            ->transformWith($this->getTransformer(BackupTransformer::class))
            ->toResponseArray();
    }

    /**
     * Deletes a backup from the panel as well as the remote source where it is currently
     * being stored.
     *
     * @throws Throwable
     */
    #[Endpoint('Delete server backup', 'Deletes a backup record and its stored archive.')]
    #[ScribeResponse(status: 204, description: 'Backup deleted.')]
    public function delete(DeleteBackupRequest $request, DeletesBackups $delete, Server $server, Backup $backup): JsonResponse
    {
        $delete->delete($backup);

        Activity::event('server:backup.delete')
            ->subject($backup)
            ->property(['name' => $backup->name, 'failed' => ! $backup->is_successful])
            ->log();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @throws Throwable
     */
    #[Endpoint('Get backup download URL', 'Returns a signed URL for downloading a server backup.')]
    #[ScribeResponse(self::SIGNED_URL_EXAMPLE, description: 'Signed backup download URL returned.')]
    #[ScribeResponse(self::BAD_REQUEST_ERROR, status: 400, description: 'The backup cannot be downloaded from its storage driver.')]
    public function download(DownloadBackupRequest $request, GeneratesBackupDownloadLinks $downloadLink, Server $server, Backup $backup): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        $url = $downloadLink->generate($backup, $user);

        Activity::event('server:backup.download')->subject($backup)->property('name', $backup->name)->log();

        return new JsonResponse([
            'object' => 'signed_url',
            'attributes' => ['url' => $url],
        ]);
    }

    /**
     * Handles restoring a backup by making a request to the Wings instance telling it
     * to begin the process of finding (or downloading) the backup and unpacking it
     * over the server files.
     *
     * If the "truncate" flag is passed through in this request then all the
     * files that currently exist on the server will be deleted before restoring.
     * Otherwise, the archive will simply be unpacked over the existing files.
     *
     * @throws Throwable
     */
    #[Endpoint('Restore server backup', 'Restores a backup over the server files.')]
    #[ScribeResponse(status: 204, description: 'Backup restore started.')]
    #[ScribeResponse(self::BAD_REQUEST_ERROR, status: 400, description: 'The server or backup is not in a restorable state.')]
    public function restore(RestoreBackupRequest $request, RestoresBackups $restore, Server $server, Backup $backup): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $validated = $request->payload();
        $truncate = $validated['truncate'];

        $restore->restore($server, $backup, $user, $truncate);

        Activity::event('server:backup.restore')
            ->subject($backup)
            ->property(['name' => $backup->name, 'truncate' => $truncate])
            ->log();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
