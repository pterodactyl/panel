<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Backup;

#[ResponseField('checksum', example: 'sha256:0123456789abcdef', nullable: true)]
#[ResponseField('completed_at', example: '2026-06-29T12:05:00+00:00', nullable: true)]
class BackupTransformer extends BaseAdminTransformer
{
    public function getResourceName(): string
    {
        return Backup::RESOURCE_NAME;
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(Backup $backup): array
    {
        return [
            'uuid' => $backup->uuid,
            'name' => $backup->name,
            'ignored_files' => $backup->ignored_files,
            'checksum' => $backup->checksum,
            'bytes' => $backup->bytes,
            'is_successful' => $backup->is_successful,
            'is_locked' => $backup->is_locked,
            'created_at' => $this->formatTimestamp($backup->created_at),
            'completed_at' => $backup->completed_at ? $this->formatTimestamp($backup->completed_at) : null,
        ];
    }
}
