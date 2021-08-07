<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Backup;
use Pterodactyl\Transformers\Api\Transformer;

class BackupTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return Backup::RESOURCE_NAME;
    }

    public function transform(Backup $backup): array
    {
        return [
            'uuid' => $backup->uuid,
            'is_successful' => $backup->is_successful,
            'is_locked' => $backup->is_locked,
            'name' => $backup->name,
            'ignored_files' => $backup->ignored_files,
            'checksum' => $backup->checksum,
            'bytes' => $backup->bytes,
            'created_at' => self::formatTimestamp($backup->created_at),
            'completed_at' => self::formatTimestamp($backup->completed_at),
        ];
    }
}
