<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

/**
 * @extends ClientResource<DaemonFileObject>
 */
class FileObjectResource extends ClientResource
{
    public function resourceName(): string
    {
        return 'file_object';
    }

    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'] ?? null,
            'mode' => $this->resource['mode'] ?? null,
            'mode_bits' => $this->resource['mode_bits'] ?? null,
            'size' => $this->resource['size'] ?? null,
            'is_file' => $this->resource['file'] ?? true,
            'is_symlink' => $this->resource['symlink'] ?? false,
            'mimetype' => $this->resource['mime'] ?? 'application/octet-stream',
            'created_at' => Date::parse($this->resource['created'] ?? '')->toAtomString(),
            'modified_at' => Date::parse($this->resource['modified'] ?? '')->toAtomString(),
        ];
    }
}
