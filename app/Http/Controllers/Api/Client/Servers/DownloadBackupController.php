<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\CarbonImmutable;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Pterodactyl\Repositories\Wings\DaemonBackupRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Backups\DownloadBackupRequest;

class DownloadBackupController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonBackupRepository
     */
    private $daemonBackupRepository;

    /**
     * @var \Illuminate\Contracts\Routing\ResponseFactory
     */
    private $responseFactory;

    /**
     * @var \Pterodactyl\Services\Nodes\NodeJWTService
     */
    private $jwtService;

    /**
     * DownloadBackupController constructor.
     *
     * @param \Pterodactyl\Repositories\Wings\DaemonBackupRepository $daemonBackupRepository
     * @param \Pterodactyl\Services\Nodes\NodeJWTService $jwtService
     * @param \Illuminate\Contracts\Routing\ResponseFactory $responseFactory
     */
    public function __construct(
        DaemonBackupRepository $daemonBackupRepository,
        NodeJWTService $jwtService,
        ResponseFactory $responseFactory
    ) {
        parent::__construct();

        $this->daemonBackupRepository = $daemonBackupRepository;
        $this->responseFactory = $responseFactory;
        $this->jwtService = $jwtService;
    }

    /**
     * Download the backup for a given server instance. For daemon local files, the file
     * will be streamed back through the Panel. For AWS S3 files, a signed URL will be generated
     * which the user is redirected to.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Backups\DownloadBackupRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Backup $backup
     * @return array
     */
    public function __invoke(DownloadBackupRequest $request, Server $server, Backup $backup)
    {
        $token = $this->jwtService
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
            ->setClaims([
                'backup_uuid' => $backup->uuid,
                'server_uuid' => $server->uuid,
            ])
            ->handle($server->node, $request->user()->id . $server->uuid);

        return [
            'object' => 'signed_url',
            'attributes' => [
                'url' => sprintf(
                    '%s/download/backup?token=%s',
                    $server->node->getConnectionAddress(),
                    $token->__toString()
                ),
            ],
        ];
    }
}
