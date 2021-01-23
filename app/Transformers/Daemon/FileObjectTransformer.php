<?php

namespace Pterodactyl\Transformers\Daemon;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class FileObjectTransformer extends BaseDaemonTransformer
{
    /**
     * An array of files we allow editing in the Panel.
     *
     * @var array
     */
    private $editable = [];

    /**
     * Transform a file object response from the daemon into a standardized response.
     *
     * @return array
     */
    public function transform(array $item)
    {
        return [
            'name' => Arr::get($item, 'name'),
            'mode' => Arr::get($item, 'mode'),
            'mode_bits' => Arr::get($item, 'mode_bits'),
            'size' => Arr::get($item, 'size'),
            'is_file' => Arr::get($item, 'file', true),
            'is_symlink' => Arr::get($item, 'symlink', false),
            'mimetype' => Arr::get($item, 'mime', 'application/octet-stream'),
            'created_at' => Carbon::parse(Arr::get($item, 'created', ''))->toIso8601String(),
            'modified_at' => Carbon::parse(Arr::get($item, 'modified', ''))->toIso8601String(),
        ];
    }

    public function getResourceName(): string
    {
        return 'file_object';
    }
}
