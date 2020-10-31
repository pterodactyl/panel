<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Repositories\Eloquent\BackupRepository;

class BackupRemoteUploadController extends Controller
{
    // I would use 1024 but I'm unsure if AWS or other S3 servers,
    // use SI gigabyte (base 10), or the proper IEC gibibyte (base 2).
    // const PART_SIZE = 5 * 1000 * 1000 * 1000;
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
     * ?
     *
     * @param \Illuminate\Http\Request $request
     * @param string $backup
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Exception
     */
    public function __invoke(Request $request, string $backup)
    {
        $size = $request->query('size', null);
        if ($size === null) {
            return new JsonResponse([], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var \Pterodactyl\Models\Backup $model */
        $model = $this->repository->findFirstWhere([[ 'uuid', '=', $backup ]]);

        // Prevent backups that have already been completed from trying to
        // be uploaded again.
        if (! is_null($model->completed_at)) {
            return new JsonResponse([], JsonResponse::HTTP_CONFLICT);
        }

        // Ensure we are using the S3 adapter.
        $adapter = $this->backupManager->adapter();
        if (! $adapter instanceof AwsS3Adapter) {
            return new JsonResponse([], JsonResponse::HTTP);
        }

        $path = sprintf('%s/%s.tar.gz', $model->server->uuid, $model->uuid);

        $client = $adapter->getClient();

        $result = $client->execute($client->getCommand('CreateMultipartUpload', [
            'Bucket' => $adapter->getBucket(),
            'Key' => $path,
            'ContentType' => 'application/x-gzip',
        ]));
        $uploadId = $result->get('UploadId');

        $completeMultipartUpload = $client->createPresignedRequest(
            $client->getCommand('CompleteMultipartUpload', [
                'Bucket' => $adapter->getBucket(),
                'Key' => $path,
                'ContentType' => 'application/x-gzip',
                'UploadId' => $uploadId,
            ]),
            CarbonImmutable::now()->addMinutes(30)
        );

        $abortMultipartUpload = $client->createPresignedRequest(
            $client->getCommand('AbortMultipartUpload', [
                'Bucket' => $adapter->getBucket(),
                'Key' => $path,
                'ContentType' => 'application/x-gzip',
                'UploadId' => $uploadId,
            ]),
            CarbonImmutable::now()->addMinutes(45)
        );

        $partCount = (int) $size / (self::PART_SIZE);

        $parts = [];
        for ($i = 0; $i < $partCount; $i++) {
            $part = $client->createPresignedRequest(
                $client->getCommand('UploadPart', [
                    'Bucket' => $adapter->getBucket(),
                    'Key' => $path,
                    'ContentType' => 'application/x-gzip',
                    'UploadId' => $uploadId,
                    'PartNumber' => $i + 1,
                ]),
                CarbonImmutable::now()->addMinutes(30)
            );

           array_push($parts, $part->getUri()->__toString());
        }

        return new JsonResponse([
            'CompleteMultipartUpload' => $completeMultipartUpload->getUri()->__toString(),
            'AbortMultipartUpload' => $abortMultipartUpload->getUri()->__toString(),
            'Parts' => $parts,
            'PartSize' => self::PART_SIZE,
        ], JsonResponse::HTTP_OK);
    }
}
