<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Http\Requests\Api\Remote\ReportBackupCompleteRequest;

class ServerBackupController extends Controller
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\BackupRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $serverRepository;

    /**
     * ServerBackupController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\BackupRepository $repository
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $serverRepository
     */
    public function __construct(BackupRepository $repository, ServerRepository $serverRepository)
    {
        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
    }

    /**
     * Updates a server backup's state in the database depending on wether or not
     * it was successful.
     *
     * @param \Pterodactyl\Http\Requests\Api\Remote\ReportBackupCompleteRequest $request
     * @param string $uuid
     * @param string $backup
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function __invoke(ReportBackupCompleteRequest $request, string $uuid, string $backup)
    {
        $server = $this->serverRepository->getByUuid($uuid);

        $where = [
            ['uuid', '=', $backup],
            ['server_id', '=', $server->id],
        ];

        if ($request->input('successful')) {
            $this->repository->updateWhere($where, [
                'sha256_hash' => $request->input('sha256_hash'),
                'bytes' => $request->input('file_size'),
                'completed_at' => Carbon::now(),
            ]);
        } else {
            $this->repository->deleteWhere($where);
        }

        return JsonResponse::create([], JsonResponse::HTTP_NO_CONTENT);
    }
}
