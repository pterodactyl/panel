<?php

namespace Pterodactyl\Services\Backups;

use Carbon\CarbonImmutable;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Backup;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Pterodactyl\Extensions\Backups\BackupManager;

class DownloadLinkService
{
    /**
     * @var \Pterodactyl\Extensions\Backups\BackupManager
     */
    private $backupManager;

    /**
     * @var \Pterodactyl\Services\Nodes\NodeJWTService
     */
    private $jwtService;

    /**
     * DownloadLinkService constructor.
     */
    public function __construct(BackupManager $backupManager, NodeJWTService $jwtService)
    {
        $this->backupManager = $backupManager;
        $this->jwtService = $jwtService;
    }

    /**
     * Returns the URL that allows for a backup to be downloaded by an individual
     * user, or by the Wings control software.
     */
    public function handle(Backup $backup, User $user): string
    {
        if ($backup->disk === Backup::ADAPTER_AWS_S3) {
            return $this->getS3BackupUrl($backup);
        }

        $token = $this->jwtService
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
            ->setClaims([
                'backup_uuid' => $backup->uuid,
                'server_uuid' => $backup->server->uuid,
            ])
            ->handle($backup->server->node, $user->id . $backup->server->uuid);

        return sprintf('%s/download/backup?token=%s', $backup->server->node->getConnectionAddress(), $token->toString());
    }

    /**
     * Returns a signed URL that allows us to download a file directly out of a non-public
     * S3 bucket by using a signed URL.
     *
     * @return string
     */
    protected function getS3BackupUrl(Backup $backup)
    {
        /** @var \League\Flysystem\AwsS3v3\AwsS3Adapter $adapter */
        $adapter = $this->backupManager->adapter(Backup::ADAPTER_AWS_S3);

        $request = $adapter->getClient()->createPresignedRequest(
            $adapter->getClient()->getCommand('GetObject', [
                'Bucket' => $adapter->getBucket(),
                'Key' => sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid),
                'ContentType' => 'application/x-gzip',
            ]),
            CarbonImmutable::now()->addMinutes(5)
        );

        return $request->getUri()->__toString();
    }
}
