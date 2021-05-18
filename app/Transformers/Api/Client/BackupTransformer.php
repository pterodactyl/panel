<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Backup;

class BackupTransformer extends BaseClientTransformer
{
    public function getResourceName(): string
    {
        return Backup::RESOURCE_NAME;
    }

    /**
     * @return array
     */
    public function transform(Backup $backup)
    {
        return [
            'uuid' => $backup->uuid,
            'is_successful' => $backup->is_successful,
            'is_locked' => $backup->is_locked,
            'name' => $backup->name,
            'ignored_files' => $backup->ignored_files,
            'checksum' => $backup->checksum,
            'bytes' => $backup->bytes,
            'created_at' => $backup->created_at->toIso8601String(),
            'completed_at' => $backup->completed_at ? $backup->completed_at->toIso8601String() : null,
        ];
    }
}
