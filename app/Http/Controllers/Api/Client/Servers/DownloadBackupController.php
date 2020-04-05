<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Lcobucci\JWT\Builder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Lcobucci\JWT\Signer\Key;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Illuminate\Http\RedirectResponse;
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
     * DownloadBackupController constructor.
     *
     * @param \Pterodactyl\Repositories\Wings\DaemonBackupRepository $daemonBackupRepository
     * @param \Illuminate\Contracts\Routing\ResponseFactory $responseFactory
     */
    public function __construct(
        DaemonBackupRepository $daemonBackupRepository,
        ResponseFactory $responseFactory
    ) {
        parent::__construct();

        $this->daemonBackupRepository = $daemonBackupRepository;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Download the backup for a given server instance. For daemon local files, the file
     * will be streamed back through the Panel. For AWS S3 files, a signed URL will be generated
     * which the user is redirected to.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Backups\DownloadBackupRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Backup $backup
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(DownloadBackupRequest $request, Server $server, Backup $backup)
    {
        $signer = new Sha256;
        $now = CarbonImmutable::now();

        $token = (new Builder)->issuedBy(config('app.url'))
            ->permittedFor($server->node->getConnectionAddress())
            ->identifiedBy(hash('sha256', $request->user()->id . $server->uuid), true)
            ->issuedAt($now->getTimestamp())
            ->canOnlyBeUsedAfter($now->subMinutes(5)->getTimestamp())
            ->expiresAt($now->addMinutes(15)->getTimestamp())
            ->withClaim('unique_id', Str::random(16))
            ->withClaim('backup_uuid', $backup->uuid)
            ->withClaim('server_uuid', $server->uuid)
            ->getToken($signer, new Key($server->node->daemonSecret));

        $location = sprintf(
            '%s/download/backup?token=%s',
            $server->node->getConnectionAddress(),
            $token->__toString()
        );

        return RedirectResponse::create($location);
    }
}
