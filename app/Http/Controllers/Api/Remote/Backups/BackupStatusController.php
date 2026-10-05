<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Contracts\Backups\CompletesBackups;
use Pterodactyl\Contracts\Backups\RestoresBackupStatuses;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Api\Remote\RemoteRequestNode;
use Pterodactyl\Http\Requests\Api\Remote\ReportBackupCompleteRequest;
use Pterodactyl\Http\Requests\Api\Remote\ReportBackupRestoreRequest;
use Pterodactyl\Models\Backup;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class BackupStatusController extends Controller
{
    /**
     * Handles updating the state of a backup.
     *
     * @throws Throwable
     */
    public function index(ReportBackupCompleteRequest $request, CompletesBackups $completeBackup, string $backup): JsonResponse
    {
        $node = RemoteRequestNode::get($request);

        $model = Backup::query()
            ->where('uuid', $backup)
            ->firstOrFail();

        // Check that the backup is "owned" by the node making the request. This avoids other nodes
        // from messing with backups that they don't own.
        $server = $model->server;
        throw_if($server->node_id !== $node->id, HttpForbiddenException::class, 'Requesting node does not have permission to access this server.');

        throw_if($model->is_successful, BadRequestHttpException::class, 'Cannot update the status of a backup that is already marked as completed.');

        $data = $request->payload();

        $completeBackup->complete($model, $data);

        Activity::event($data['successful'] ? 'server:backup.complete' : 'server:backup.fail')
            ->subject($model, $model->server)
            ->property('name', $model->name)
            ->log();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Resets the server status to null after a restore attempt - even a failed restore is
     * recoverable by retrying or reinstalling, and the successful flag only selects which
     * activity log event is recorded.
     *
     * @throws Throwable
     */
    public function restore(ReportBackupRestoreRequest $request, RestoresBackupStatuses $restoreStatus, string $backup): JsonResponse
    {
        $model = $restoreStatus->restoreStatus($backup, RemoteRequestNode::get($request));

        $successful = $request->payload()['successful'];

        Activity::event($successful ? 'server:backup.restore-complete' : 'server:backup.restore-failed')
            ->subject($model, $model->server)
            ->property('name', $model->name)
            ->log();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
