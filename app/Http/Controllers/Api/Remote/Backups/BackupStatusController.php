<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
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
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function __invoke(ReportBackupCompleteRequest $request, string $backup)
    {
        /** @var \Pterodactyl\Models\Backup $backup */
        $backup = $this->repository->findFirstWhere([['uuid', '=', $backup]]);

        if ($request->input('successful')) {
            $this->repository->update($backup->id, [
                'sha256_hash' => $request->input('checksum'),
                'bytes' => $request->input('size'),
                'completed_at' => Carbon::now(),
            ], true, true);
        } else {
            $this->repository->delete($backup->id);
        }

        return JsonResponse::create([], JsonResponse::HTTP_NO_CONTENT);
    }
}
