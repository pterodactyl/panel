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
        $size = (int) $request->query('size');
        if (empty($size)) {
            throw new BadRequestHttpException('A non-empty "size" query parameter must be provided.');
        }

        /** @var \Pterodactyl\Models\Backup $backup */
        $backup = Backup::query()->where('uuid', $backup)->firstOrFail();

        // Prevent backups that have already been completed from trying to
        // be uploaded again.
        if (! is_null($backup->completed_at)) {
            return new JsonResponse([], JsonResponse::HTTP_CONFLICT);
        }

        // Ensure we are using the S3 adapter.
        $adapter = $this->backupManager->adapter();
        if (! $adapter instanceof AwsS3Adapter) {
            throw new BadRequestHttpException('The configured backup adapter is not an S3 compatible adapter.');
        }

        // The path where backup will be uploaded to
        $path = sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid);

        // Get the S3 client
        $client = $adapter->getClient();
        $expires = CarbonImmutable::now()->addMinutes(config('backups.presigned_url_lifespan', 60));

        // Params for generating the presigned urls
        $params = [
            'Bucket' => $adapter->getBucket(),
            'Key' => $path,
            'ContentType' => 'application/x-gzip',
        ];

        // Execute the CreateMultipartUpload request
        $result = $client->execute($client->getCommand('CreateMultipartUpload', $params));

        // Get the UploadId from the CreateMultipartUpload request, this is needed to create
        // the other presigned urls.
        $params['UploadId'] = $result->get('UploadId');

        // Create as many UploadPart presigned urls as needed
        $parts = [];
        for ($i = 0; $i < ($size / self::PART_SIZE); $i++) {
            $parts[] = $client->createPresignedRequest(
                $client->getCommand('UploadPart', array_merge($params, ['PartNumber' => $i + 1])), $expires
            )->getUri()->__toString();
        }

        return new JsonResponse([
            'upload_id' => $params['UploadId'],
            'parts' => $parts,
            'part_size' => self::PART_SIZE,
        ]);
    }
}
