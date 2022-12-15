<?php

namespace Pterodactyl\Transformers\Api\Client;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Pterodactyl\Transformers\Api\Transformer;

class FileObjectTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return 'file_object';
    }

    /**
     * Transform a file object response from the daemon into a standardized response.
     */
    public function transform(array $model): array
    {
        return [
            'name' => Arr::get($model, 'name'),
            'mode' => Arr::get($model, 'mode'),
            'mode_bits' => Arr::get($model, 'mode_bits'),
            'size' => Arr::get($model, 'size'),
            'is_file' => Arr::get($model, 'file', true),
            'is_symlink' => Arr::get($model, 'symlink', false),
            'mimetype' => Arr::get($model, 'mime', 'application/octet-stream'),
            'created_at' => Carbon::parse(Arr::get($model, 'created', ''))->toAtomString(),
            'modified_at' => Carbon::parse(Arr::get($model, 'modified', ''))->toAtomString(),
        ];
    }
}
