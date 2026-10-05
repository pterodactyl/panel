<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Backups;

use Carbon\CarbonImmutable;
use LogicException;
use Pterodactyl\Contracts\Backups\GeneratesBackupDownloadLinks;
use Pterodactyl\Enum\JwtScope;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class GenerateBackupDownloadLink implements GeneratesBackupDownloadLinks
{
    public function __construct(private BackupManager $backupManager, private NodeJWTService $jwtService) {}

    /**
     * Returns the URL that allows for a backup to be downloaded by an individual
     * user, or by the Wings control software.
     */
    public function generate(Backup $backup, User $user): string
    {
        throw_if($backup->disk !== Backup::ADAPTER_AWS_S3 && $backup->disk !== Backup::ADAPTER_WINGS, BadRequestHttpException::class, 'The backup requested references an unknown disk driver type and cannot be downloaded.');

        if ($backup->disk === Backup::ADAPTER_AWS_S3) {
            return $this->getS3BackupUrl($backup);
        }

        $token = $this->jwtService
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
            ->setUser($user)
            ->setClaims([
                'backup_uuid' => $backup->uuid,
                'server_uuid' => $backup->server->uuid,
            ])
            ->setScopes(JwtScope::BackupDownload)
            ->handle($backup->server->node, $user->id.$backup->server->uuid);

        return sprintf('%s/download/backup?token=%s', $backup->server->node->getConnectionAddress(), $token->toString());
    }

    /**
     * Returns a signed URL that allows us to download a file directly out of a non-public
     * S3 bucket by using a signed URL.
     */
    private function getS3BackupUrl(Backup $backup): string
    {
        $adapter = $this->backupManager->adapter(Backup::ADAPTER_AWS_S3);
        throw_unless($adapter instanceof S3Filesystem, LogicException::class, 'The S3 backup adapter is not configured correctly.');

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
