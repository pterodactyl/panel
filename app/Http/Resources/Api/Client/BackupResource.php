<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Backup;

/**
 * @extends ClientResource<Backup>
 */
#[ResponseField('checksum', example: 'sha256:0123456789abcdef', nullable: true)]
#[ResponseField('completed_at', example: '2026-06-29T12:05:00+00:00', nullable: true)]
class BackupResource extends ClientResource
{
    public function resourceName(): string
    {
        return Backup::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'is_successful' => $this->resource->is_successful,
            'is_locked' => $this->resource->is_locked,
            'name' => $this->resource->name,
            'ignored_files' => $this->resource->ignored_files,
            'checksum' => $this->resource->checksum,
            'bytes' => $this->resource->bytes,
            'created_at' => $this->resource->created_at->toAtomString(),
            'completed_at' => $this->resource->completed_at ? $this->resource->completed_at->toAtomString() : null,
        ];
    }
}
