<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Pterodactyl\Models\Backup;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

interface PresignsBackupUploads
{
    /**
     * Open a multipart upload for the backup in S3 and return one presigned URL per part Wings
     * needs to upload an archive of the given size.
     *
     * @return BackupUploadParts
     *
     * @throws BadRequestHttpException
     */
    public function presign(Backup $backup, int $size): array;
}
