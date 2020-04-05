<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Backup;

class BackupTransformer extends BaseClientTransformer
{
    /**
     * @return string
     */
    public function getResourceName(): string
    {
        return Backup::RESOURCE_NAME;
    }

    /**
     * @param \Pterodactyl\Models\Backup $backup
     * @return array
     */
    public function transform(Backup $backup)
    {
        return [
            'uuid' => $backup->uuid,
            'name' => $backup->name,
            'ignored_files' => $backup->ignored_files,
            'sha256_hash' => $backup->sha256_hash,
            'bytes' => $backup->bytes,
            'created_at' => $backup->created_at->toIso8601String(),
            'completed_at' => $backup->completed_at ? $backup->completed_at->toIso8601String() : null,
        ];
    }
}
