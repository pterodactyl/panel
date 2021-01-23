<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\CarbonImmutable;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Repositories\Wings\DaemonBackupRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
     * @var \Pterodactyl\Extensions\Backups\BackupManager
     */
    private $backupManager;

    /**
     * DownloadBackupController constructor.
     *
     * @param \Pterodactyl\Repositories\Wings\DaemonBackupRepository $daemonBackupRepository
     * @param \Pterodactyl\Services\Nodes\NodeJWTService $jwtService
     * @param \Pterodactyl\Extensions\Backups\BackupManager $backupManager
     * @param \Illuminate\Contracts\Routing\ResponseFactory $responseFactory
     */
    public function __construct(
        DaemonBackupRepository $daemonBackupRepository,
        NodeJWTService $jwtService,
        BackupManager $backupManager,
        ResponseFactory $responseFactory
    ) {
        parent::__construct();

        $this->daemonBackupRepository = $daemonBackupRepository;
        $this->responseFactory = $responseFactory;
        $this->jwtService = $jwtService;
        $this->backupManager = $backupManager;
    }

    /**
     * Download the backup for a given server instance. For daemon local files, the file
     * will be streamed back through the Panel. For AWS S3 files, a signed URL will be generated
     * which the user is redirected to.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Backups\DownloadBackupRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\Backup $backup
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(DownloadBackupRequest $request, Server $server, Backup $backup)
    {
        switch ($backup->disk) {
            case Backup::ADAPTER_WINGS:
                $url = $this->getLocalBackupUrl($backup, $server, $request->user());
                break;
            case Backup::ADAPTER_AWS_S3:
                $url = $this->getS3BackupUrl($backup, $server);
                break;
            default:
                throw new BadRequestHttpException;
        }

        return new JsonResponse([
            'object' => 'signed_url',
            'attributes' => [
                'url' => $url,
            ],
        ]);
    }

    /**
     * Returns a signed URL that allows us to download a file directly out of a non-public
     * S3 bucket by using a signed URL.
     *
     * @param \Pterodactyl\Models\Backup $backup
     * @param \Pterodactyl\Models\Server $server
     * @return string
     */
    protected function getS3BackupUrl(Backup $backup, Server $server)
    {
        /** @var \League\Flysystem\AwsS3v3\AwsS3Adapter $adapter */
        $adapter = $this->backupManager->adapter(Backup::ADAPTER_AWS_S3);

        $client = $adapter->getClient();

        $request = $client->createPresignedRequest(
            $client->getCommand('GetObject', [
                'Bucket' => $adapter->getBucket(),
                'Key' => sprintf('%s/%s.tar.gz', $server->uuid, $backup->uuid),
                'ContentType' => 'application/x-gzip',
            ]),
            CarbonImmutable::now()->addMinutes(5)
        );

        return $request->getUri()->__toString();
    }

    /**
     * Returns a download link a backup stored on a wings instance.
     *
     * @param \Pterodactyl\Models\Backup $backup
     * @param \Pterodactyl\Models\Server $server
     * @param \Pterodactyl\Models\User $user
     * @return string
     */
    protected function getLocalBackupUrl(Backup $backup, Server $server, User $user)
    {
        $token = $this->jwtService
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
            ->setClaims([
                'backup_uuid' => $backup->uuid,
                'server_uuid' => $server->uuid,
            ])
            ->handle($server->node, $user->id . $server->uuid);

        return sprintf(
            '%s/download/backup?token=%s',
            $server->node->getConnectionAddress(),
            $token->toString()
        );
    }
}
