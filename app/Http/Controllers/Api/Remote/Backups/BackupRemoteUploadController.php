<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Pterodactyl\Models\Backup;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BackupRemoteUploadController extends Controller
{
    public const DEFAULT_MAX_PART_SIZE = 5 * 1024 * 1024 * 1024;

    /**
     * BackupRemoteUploadController constructor.
     */
    public function __construct(private BackupManager $backupManager)
    {
    }

    /**
     * Returns the required presigned urls to upload a backup to S3 cloud storage.
     *
     * @throws \Exception
     * @throws \Throwable
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function __invoke(Request $request, string $backup): JsonResponse
    {
        // Get the node associated with the request.
        /** @var \Pterodactyl\Models\Node $node */
        $node = $request->attributes->get('node');

        // Get the size query parameter.
        $size = (int) $request->query('size');
        if (empty($size)) {
            throw new BadRequestHttpException('A non-empty "size" query parameter must be provided.');
        }

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

        // Prevent backups that have already been completed from trying to
        // be uploaded again.
        if (!is_null($model->completed_at)) {
            throw new ConflictHttpException('This backup is already in a completed state.');
        }

        // Ensure we are using the S3 adapter.
        $adapter = $this->backupManager->adapter();
        if (!$adapter instanceof S3Filesystem) {
            throw new BadRequestHttpException('The configured backup adapter is not an S3 compatible adapter.');
        }

        // The path where backup will be uploaded to
        $path = sprintf('%s/%s.tar.gz', $model->server->uuid, $model->uuid);

        // Get the S3 client
        $client = $adapter->getClient();
        $expires = CarbonImmutable::now()->addMinutes(config('backups.presigned_url_lifespan', 60));

        // Params for generating the presigned urls
        $params = [
            'Bucket' => $adapter->getBucket(),
            'Key' => $path,
            'ContentType' => 'application/x-gzip',
        ];

        $storageClass = config('backups.disks.s3.storage_class');
        if (!is_null($storageClass)) {
            $params['StorageClass'] = $storageClass;
        }

        // Execute the CreateMultipartUpload request
        $result = $client->execute($client->getCommand('CreateMultipartUpload', $params));

        // Get the UploadId from the CreateMultipartUpload request, this is needed to create
        // the other presigned urls.
        $params['UploadId'] = $result->get('UploadId');

        // Retrieve configured part size
        $maxPartSize = $this->getConfiguredMaxPartSize();

        // Create as many UploadPart presigned urls as needed
        $parts = [];
        for ($i = 0; $i < ($size / $maxPartSize); ++$i) {
            $parts[] = $client->createPresignedRequest(
                $client->getCommand('UploadPart', array_merge($params, ['PartNumber' => $i + 1])),
                $expires
            )->getUri()->__toString();
        }

        // Set the upload_id on the backup in the database.
        $model->update(['upload_id' => $params['UploadId']]);

        return new JsonResponse([
            'parts' => $parts,
            'part_size' => $maxPartSize,
        ]);
    }

    /**
     * Get the configured maximum size of a single part in the multipart upload.
     *
     * The function tries to retrieve a configured value from the configuration.
     * If no value is specified, a fallback value will be used.
     *
     * Note if the received config cannot be converted to int (0), is zero or is negative,
     * the fallback value will be used too.
     *
     * The fallback value is {@see BackupRemoteUploadController::DEFAULT_MAX_PART_SIZE}.
     */
    private function getConfiguredMaxPartSize(): int
    {
        $maxPartSize = (int) config('backups.max_part_size', self::DEFAULT_MAX_PART_SIZE);
        if ($maxPartSize <= 0) {
            $maxPartSize = self::DEFAULT_MAX_PART_SIZE;
        }

        return $maxPartSize;
    }
}
