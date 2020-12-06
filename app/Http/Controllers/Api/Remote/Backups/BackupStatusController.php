<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Carbon\CarbonImmutable;
use Pterodactyl\Models\Backup;
use Illuminate\Http\JsonResponse;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Requests\Api\Remote\ReportBackupCompleteRequest;

class BackupStatusController extends Controller
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\BackupRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Extensions\Backups\BackupManager
     */
    private $backupManager;

    /**
     * BackupStatusController constructor.
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
     * Handles updating the state of a backup.
     *
     * @param \Pterodactyl\Http\Requests\Api\Remote\ReportBackupCompleteRequest $request
     * @param string $backup
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Exception
     */
    public function __invoke(ReportBackupCompleteRequest $request, string $backup)
    {
        /** @var \Pterodactyl\Models\Backup $model */
        $model = $this->repository->findFirstWhere([[ 'uuid', '=', $backup ]]);

        if (! is_null($model->completed_at)) {
            throw new BadRequestHttpException(
                'Cannot update the status of a backup that is already marked as completed.'
            );
        }

        $successful = $request->input('successful') ? true : false;

        // TODO: Still run s3 code even if this fails.
        $model->forceFill([
            'is_successful' => $successful,
            'checksum' => $successful ? ($request->input('checksum_type') . ':' . $request->input('checksum')) : null,
            'bytes' => $successful ? $request->input('size') : 0,
            'completed_at' => CarbonImmutable::now(),
        ])->save();

        // Check if we are using the s3 backup adapter.
        $adapter = $this->backupManager->adapter();
        if ($adapter instanceof AwsS3Adapter) {
            /** @var \Pterodactyl\Models\Backup $backup */
            $backup = Backup::query()->where('uuid', $backup)->firstOrFail();

            $client = $adapter->getClient();

            $params = [
                'Bucket' => $adapter->getBucket(),
                'Key' => sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid),
                'UploadId' => $request->input('upload_id'),
            ];

            // If the backup was not successful, send an AbortMultipartUpload request.
            if (! $successful) {
                $client->execute($client->getCommand('AbortMultipartUpload', $params));
                return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
            }

            // Otherwise send a CompleteMultipartUpload request.
            $params['MultipartUpload'] = [
                'Parts' => $client->execute($client->getCommand('ListParts', $params))['Parts'],
            ];
            $client->execute($client->getCommand('CompleteMultipartUpload', $params));
        }

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
