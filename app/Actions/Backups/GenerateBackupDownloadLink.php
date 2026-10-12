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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class GenerateBackupDownloadLink implements GeneratesBackupDownloadLinks
{
    public function __construct(private BackupManager $backupManager) {}

    public function generate(Backup $backup, User $user): string
    {
        throw_if($backup->disk !== Backup::ADAPTER_AWS_S3 && $backup->disk !== Backup::ADAPTER_WINGS, BadRequestHttpException::class, 'The backup requested references an unknown disk driver type and cannot be downloaded.');

        if ($backup->disk === Backup::ADAPTER_AWS_S3) {
            return $this->getS3BackupUrl($backup);
        }

        $token = $backup->server->node->createJwt(
            JwtScope::BackupDownload,
            identifiedBy: $user->id.$backup->server->uuid,
            expiresAt: CarbonImmutable::now()->addMinutes(15),
            user: $user,
            claims: ['backup_uuid' => $backup->uuid, 'server_uuid' => $backup->server->uuid],
        );

        return sprintf('%s/download/backup?token=%s', $backup->server->node->getConnectionAddress(), $token->toString());
    }

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
