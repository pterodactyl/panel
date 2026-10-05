<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Backups;

use Pterodactyl\Models\Backup;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

interface PresignsBackupUploads
{
    /** @return BackupUploadParts
     * @throws BadRequestHttpException
     */
    public function presign(Backup $backup, int $size): array;
}
