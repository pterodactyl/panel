<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Pterodactyl\Models\Backup;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Requests\Api\Remote\ReportBackupCompleteRequest;

class BackupStatusController extends Controller
{
    /**
     * BackupStatusController constructor.
     */
    public function __construct(private BackupManager $backupManager)
    {
    }

    /**
     * Handles updating the state of a backup.
     *
     * @throws \Throwable
     */
    public function index(ReportBackupCompleteRequest $request, string $backup): JsonResponse
    {
        // Get the node associated with the request.
        /** @var \Pterodactyl\Models\Node $node */
        $node = $request->attributes->get('node');

        /** @var Backup $model */
        $model = Backup::query()
            ->where('uuid', $backup)
            ->firstOrFail();

        // Check that the backup is "owned" by the node making the request. This avoids other nodes
        // from messing with backups that they don't own.
        /** @var \Pterodactyl\Models\Server $server */
        $server = $model->server;
        if ($server->node_id !== $node->id) {
            throw new HttpForbiddenException('You do not have permission to access that backup.');
        }

        if ($model->is_successful) {
            throw new BadRequestHttpException('Cannot update the status of a backup that is already marked as completed.');
        }

        $action = $request->boolean('successful') ? 'server:backup.complete' : 'server:backup.fail';
        $log = Activity::event($action)->subject($model, $model->server)->property('name', $model->name);

        $log->transaction(function () use ($model, $request) {
            $successful = $request->boolean('successful');

            $model->fill([
                'is_successful' => $successful,
                // Change the lock state to unlocked if this was a failed backup so that it can be
                // deleted easily. Also does not make sense to have a locked backup on the system
                // that is failed.
                'is_locked' => $successful ? $model->is_locked : false,
                'checksum' => $successful ? ($request->input('checksum_type') . ':' . $request->input('checksum')) : null,
                'bytes' => $successful ? $request->input('size') : 0,
                'completed_at' => CarbonImmutable::now(),
            ])->save();

            // Check if we are using the s3 backup adapter. If so, make sure we mark the backup as
            // being completed in S3 correctly.
            $adapter = $this->backupManager->adapter();
            if ($adapter instanceof S3Filesystem) {
                $this->completeMultipartUpload($model, $adapter, $successful, $request->input('parts'));
            }
        });

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Handles toggling the restoration status of a server. The server status field should be
     * set back to null, even if the restoration failed. This is not an unsolvable state for
     * the server, and the user can keep trying to restore, or just use the reinstall button.
     *
     * The only thing the successful field does is update the entry value for the audit logs
     * table tracking for this restoration.
     *
     * @throws \Throwable
     */
    public function restore(Request $request, string $backup): JsonResponse
    {
        /** @var Backup $model */
        $model = Backup::query()->where('uuid', $backup)->firstOrFail();

        $model->server->update(['status' => null]);

        Activity::event($request->boolean('successful') ? 'server:backup.restore-complete' : 'server.backup.restore-failed')
            ->subject($model, $model->server)
            ->property('name', $model->name)
            ->log();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Marks a multipart upload in a given S3-compatible instance as failed or successful for
     * the given backup.
     *
     * @throws \Exception
     * @throws DisplayException
     */
    protected function completeMultipartUpload(Backup $backup, S3Filesystem $adapter, bool $successful, ?array $parts): void
    {
        // This should never really happen, but if it does don't let us fall victim to Amazon's
        // wildly fun error messaging. Just stop the process right here.
        if (empty($backup->upload_id)) {
            // A failed backup doesn't need to error here, this can happen if the backup encounters
            // an error before we even start the upload. AWS gives you tooling to clear these failed
            // multipart uploads as needed too.
            if (!$successful) {
                return;
            }

            throw new DisplayException('Cannot complete backup request: no upload_id present on model.');
        }

        $params = [
            'Bucket' => $adapter->getBucket(),
            'Key' => sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid),
            'UploadId' => $backup->upload_id,
        ];

        $client = $adapter->getClient();
        if (!$successful) {
            $client->execute($client->getCommand('AbortMultipartUpload', $params));

            return;
        }

        // Otherwise send a CompleteMultipartUpload request.
        $params['MultipartUpload'] = [
            'Parts' => [],
        ];

        if (is_null($parts)) {
            $params['MultipartUpload']['Parts'] = $client->execute($client->getCommand('ListParts', $params))['Parts'];
        } else {
            foreach ($parts as $part) {
                $params['MultipartUpload']['Parts'][] = [
                    'ETag' => $part['etag'],
                    'PartNumber' => $part['part_number'],
                ];
            }
        }

        $client->execute($client->getCommand('CompleteMultipartUpload', $params));
    }
}
