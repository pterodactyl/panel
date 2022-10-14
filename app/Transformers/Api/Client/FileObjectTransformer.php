<?php

namespace Pterodactyl\Transformers\Api\Client;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class FileObjectTransformer extends BaseClientTransformer
{
    /**
     * Transform a file object response from the daemon into a standardized response.
     */
    public function transform(array $item): array
    {
        return [
            'name' => Arr::get($item, 'name'),
            'mode' => Arr::get($item, 'mode'),
            'mode_bits' => Arr::get($item, 'mode_bits'),
            'size' => Arr::get($item, 'size'),
            'is_file' => Arr::get($item, 'file', true),
            'is_symlink' => Arr::get($item, 'symlink', false),
            'mimetype' => Arr::get($item, 'mime', 'application/octet-stream'),
            'created_at' => Carbon::parse(Arr::get($item, 'created', ''))->toAtomString(),
            'modified_at' => Carbon::parse(Arr::get($item, 'modified', ''))->toAtomString(),
        ];
    }

    public function getResourceName(): string
    {
        return 'file_object';
    }
}
