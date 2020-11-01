<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
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
     * BackupStatusController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\BackupRepository $repository
     */
    public function __construct(BackupRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Handles updating the state of a backup.
     *
     * @param \Pterodactyl\Http\Requests\Api\Remote\ReportBackupCompleteRequest $request
     * @param string $backup
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
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
        $model->forceFill([
            'is_successful' => $successful,
            'checksum' => $successful ? ($request->input('checksum_type') . ':' . $request->input('checksum')) : null,
            'bytes' => $successful ? $request->input('size') : 0,
            'completed_at' => CarbonImmutable::now(),
        ])->save();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
