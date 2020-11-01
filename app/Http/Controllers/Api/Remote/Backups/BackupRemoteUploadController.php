<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Pterodactyl\Models\Backup;
use Illuminate\Http\JsonResponse;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BackupRemoteUploadController extends Controller
{
    const PART_SIZE = 5 * 1024 * 1024 * 1024;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\BackupRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Extensions\Backups\BackupManager
     */
    private $backupManager;

    /**
     * BackupRemoteUploadController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\BackupRepository $repository
     * @param \Pterodactyl\Extensions\Backups\BackupManager $backupManager
     */
    public function __construct(BackupRepository $repository, BackupManager $backupManager)
    {
        $this->repository = $repository;
        $this->backupManager = $backupManager;
    }

    /**
     * Returns the required presigned urls to upload a backup to S3 cloud storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $backup
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function __invoke(Request $request, string $backup)
    {
        // Get the size query parameter.
        $size = $request->query('size', null);
        if (is_null($size)) {
            throw new BadRequestHttpException('Missing size query parameter.');
        }

        /** @var \Pterodactyl\Models\Backup $model */
        $model = Backup::query()->where([[ 'uuid', '=', $backup ]])->firstOrFail();

        // Prevent backups that have already been completed from trying to
        // be uploaded again.
        if (! is_null($model->completed_at)) {
            return new JsonResponse([], JsonResponse::HTTP_CONFLICT);
        }

        // Ensure we are using the S3 adapter.
        $adapter = $this->backupManager->adapter();
        if (! $adapter instanceof AwsS3Adapter) {
            throw new BadRequestHttpException('Backups are not using the s3 storage driver');
        }

        // The path where backup will be uploaded to
        $path = sprintf('%s/%s.tar.gz', $model->server->uuid, $model->uuid);

        // Get the S3 client
        $client = $adapter->getClient();

        // Params for generating the presigned urls
        $params = [
            'Bucket' => $adapter->getBucket(),
            'Key' => $path,
            'ContentType' => 'application/x-gzip',
        ];

        // Execute the CreateMultipartUpload request
        $result = $client->execute($client->getCommand('CreateMultipartUpload', $params));

        // Get the UploadId from the CreateMultipartUpload request,
        // this is needed to create the other presigned urls
        $uploadId = $result->get('UploadId');

        // Create a CompleteMultipartUpload presigned url
        $completeMultipartUpload = $client->createPresignedRequest(
            $client->getCommand(
                'CompleteMultipartUpload',
                array_merge($params, [
                    'UploadId' => $uploadId,
                ])
            ),
            CarbonImmutable::now()->addMinutes(30)
        );

        // Create a AbortMultipartUpload presigned url
        $abortMultipartUpload = $client->createPresignedRequest(
            $client->getCommand(
                'AbortMultipartUpload',
                array_merge($params, [
                    'UploadId' => $uploadId,
                ])
            ),
            CarbonImmutable::now()->addMinutes(45)
        );

        // Calculate the number of parts needed to upload the backup
        $partCount = (int) $size / (self::PART_SIZE);

        // Create as many UploadPart presigned urls as needed
        $parts = [];
        for ($i = 0; $i < $partCount; $i++) {
            $part = $client->createPresignedRequest(
                $client->getCommand(
                    'UploadPart',
                    array_merge($params, [
                        'UploadId' => $uploadId,
                        'PartNumber' => $i + 1,
                    ])
                ),
                CarbonImmutable::now()->addMinutes(30)
            );

           array_push($parts, $part->getUri()->__toString());
        }

        return new JsonResponse([
            'complete_multipart_upload' => $completeMultipartUpload->getUri()->__toString(),
            'abort_multipart_upload' => $abortMultipartUpload->getUri()->__toString(),
            'parts' => $parts,
            'part_size' => self::PART_SIZE,
        ]);
    }
}
