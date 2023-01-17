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

    public function transform(Backup $model): array
    {
        return [
            'uuid' => $model->uuid,
            'is_successful' => $model->is_successful,
            'is_locked' => $model->is_locked,
            'name' => $model->name,
            'ignored_files' => $model->ignored_files,
            'checksum' => $model->checksum,
            'bytes' => $model->bytes,
            'created_at' => self::formatTimestamp($model->created_at),
            'completed_at' => self::formatTimestamp($model->completed_at),
        ];
    }
}
