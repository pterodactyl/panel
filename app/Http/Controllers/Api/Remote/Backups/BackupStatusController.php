<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Backups;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
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
     */
    public function __invoke(ReportBackupCompleteRequest $request, string $backup)
    {
        $this->repository->updateWhere([['uuid', '=', $backup]], [
            'is_successful' => $request->input('successful') ? true : false,
            'checksum' => $request->input('checksum_type') . ':' . $request->input('checksum'),
            'bytes' => $request->input('size'),
            'completed_at' => CarbonImmutable::now(),
        ]);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
