<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Backup;

/**
 * @extends AdminResource<Backup>
 */
#[ResponseField('checksum', example: 'sha256:0123456789abcdef', nullable: true)]
#[ResponseField('completed_at', example: '2026-06-29T12:05:00+00:00', nullable: true)]
class BackupResource extends AdminResource
{
    public function resourceName(): string
    {
        return Backup::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'name' => $this->resource->name,
            'ignored_files' => $this->resource->ignored_files,
            'checksum' => $this->resource->checksum,
            'bytes' => $this->resource->bytes,
            'is_successful' => $this->resource->is_successful,
            'is_locked' => $this->resource->is_locked,
            'created_at' => $this->formatTimestamp($this->resource->created_at),
            'completed_at' => $this->resource->completed_at ? $this->formatTimestamp($this->resource->completed_at) : null,
        ];
    }
}
