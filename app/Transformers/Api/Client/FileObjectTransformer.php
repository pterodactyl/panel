<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Client;

use Illuminate\Support\Facades\Date;

class FileObjectTransformer extends BaseClientTransformer
{
    /**
     * Transform a file object response from the daemon into a standardized response.
     *
     * @param  DaemonFileObject  $item
     * @return ApiPayload
     */
    public function transform(array $item): array
    {
        return [
            'name' => $item['name'] ?? null,
            'mode' => $item['mode'] ?? null,
            'mode_bits' => $item['mode_bits'] ?? null,
            'size' => $item['size'] ?? null,
            'is_file' => $item['file'] ?? true,
            'is_symlink' => $item['symlink'] ?? false,
            'mimetype' => $item['mime'] ?? 'application/octet-stream',
            'created_at' => Date::parse($item['created'] ?? '')->toAtomString(),
            'modified_at' => Date::parse($item['modified'] ?? '')->toAtomString(),
        ];
    }

    public function getResourceName(): string
    {
        return 'file_object';
    }
}
