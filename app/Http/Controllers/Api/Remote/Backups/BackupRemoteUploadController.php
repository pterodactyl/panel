<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pterodactyl\Contracts\Backups\PresignsBackupUploads;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Api\Remote\RemoteRequestNode;
use Pterodactyl\Models\Backup;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class BackupRemoteUploadController extends Controller
{
    /**
     * Returns the required presigned urls to upload a backup to S3 cloud storage.
     *
     * @throws Exception
     * @throws Throwable
     * @throws ModelNotFoundException
     */
    public function __invoke(Request $request, PresignsBackupUploads $presignBackupUpload, string $backup): JsonResponse
    {
        $node = RemoteRequestNode::get($request);

        // Get the size query parameter.
        $size = $request->integer('size');
        throw_if(empty($size), BadRequestHttpException::class, 'A non-empty "size" query parameter must be provided.');

        $model = Backup::query()->where('uuid', $backup)->firstOrFail();

        // Check that the backup is "owned" by the node making the request. This avoids other nodes
        // from messing with backups that they don't own.
        $server = $model->server;
        throw_if($server->node_id !== $node->id, HttpForbiddenException::class, 'Requesting node does not have permission to access this server.');

        // Prevent backups that have already been completed from trying to
        // be uploaded again.
        throw_if(($model->completed_at) !== null, ConflictHttpException::class, 'This backup is already in a completed state.');

        return new JsonResponse($presignBackupUpload->presign($model, $size));
    }
}
