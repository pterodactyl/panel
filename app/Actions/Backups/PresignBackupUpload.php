<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Backups;

use Carbon\CarbonImmutable;
use Pterodactyl\Contracts\Backups\PresignsBackupUploads;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Extensions\Filesystem\S3Filesystem;
use Pterodactyl\Models\Backup;
use Pterodactyl\Support\JsonValueGuard;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class PresignBackupUpload implements PresignsBackupUploads
{
    public const int DEFAULT_MAX_PART_SIZE = 5 * 1024 * 1024 * 1024;

    public function __construct(private BackupManager $backupManager) {}

    /**
     * Opens a multipart upload for the backup in S3 and returns one presigned URL per part
     * Wings needs to upload for an archive of the given size.
     *
     * @return BackupUploadParts
     *
     * @throws BadRequestHttpException
     */
    public function presign(Backup $backup, int $size): array
    {
        $adapter = $this->backupManager->adapter();
        throw_unless($adapter instanceof S3Filesystem, BadRequestHttpException::class, 'The configured backup adapter is not an S3 compatible adapter.');

        $client = $adapter->getClient();
        $expires = CarbonImmutable::now()->addMinutes(JsonValueGuard::integer(config('backups.presigned_url_lifespan', 60)));

        $params = [
            'Bucket' => $adapter->getBucket(),
            'Key' => sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid),
            'ContentType' => 'application/x-gzip',
        ];

        $storageClass = config('backups.disks.s3.storage_class');
        if (($storageClass) !== null) {
            $params['StorageClass'] = $storageClass;
        }

        $result = $client->execute($client->getCommand('CreateMultipartUpload', $params));

        $params['UploadId'] = $result->get('UploadId');

        $maxPartSize = $this->maxPartSize();

        $parts = [];
        for ($i = 0; $i < ($size / $maxPartSize); $i++) {
            $parts[] = $client->createPresignedRequest(
                $client->getCommand('UploadPart', array_merge($params, ['PartNumber' => $i + 1])),
                $expires
            )->getUri()->__toString();
        }

        $backup->update(['upload_id' => $params['UploadId']]);

        return [
            'parts' => $parts,
            'part_size' => $maxPartSize,
        ];
    }

    /**
     * The configured maximum size of a single multipart upload part, falling back to
     * DEFAULT_MAX_PART_SIZE when the configured value is missing, non-numeric, zero, or negative.
     */
    private function maxPartSize(): int
    {
        $maxPartSize = filter_var(
            config('backups.max_part_size', self::DEFAULT_MAX_PART_SIZE),
            FILTER_VALIDATE_INT,
        );
        if ($maxPartSize === false || $maxPartSize <= 0) {
            return self::DEFAULT_MAX_PART_SIZE;
        }

        return $maxPartSize;
    }
}
