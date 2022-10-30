<?php

namespace Pterodactyl\Services\Eggs;

use Illuminate\Support\Arr;
use Pterodactyl\Models\Egg;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;

class EggParserService
{
    /**
     * Takes an uploaded file and parses out the egg configuration from within.
     *
     * @throws \JsonException
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException
     */
    public function handle(UploadedFile $file): array
    {
        if ($file->getError() !== UPLOAD_ERR_OK || !$file->isFile()) {
            throw new InvalidFileUploadException('The selected file is not valid and cannot be imported.');
        }

        /** @var array $parsed */
        $parsed = json_decode($file->openFile()->fread($file->getSize()), true, 512, JSON_THROW_ON_ERROR);
        if (!in_array(Arr::get($parsed, 'meta.version') ?? '', ['PTDL_v1', 'PTDL_v2'])) {
            throw new InvalidFileUploadException('The JSON file provided is not in a format that can be recognized.');
        }

        return $this->convertToV2($parsed);
    }

    /**
     * Fills the provided model with the parsed JSON data.
     */
    public function fillFromParsed(Egg $model, array $parsed): Egg
    {
        return $model->forceFill([
            'name' => Arr::get($parsed, 'name'),
            'description' => Arr::get($parsed, 'description'),
            'features' => Arr::get($parsed, 'features'),
            'docker_images' => Arr::get($parsed, 'docker_images'),
            'file_denylist' => Collection::make(Arr::get($parsed, 'file_denylist'))
                ->filter(fn ($value) => !empty($value)),
            'update_url' => Arr::get($parsed, 'meta.update_url'),
            'config_files' => Arr::get($parsed, 'config.files'),
            'config_startup' => Arr::get($parsed, 'config.startup'),
            'config_logs' => Arr::get($parsed, 'config.logs'),
            'config_stop' => Arr::get($parsed, 'config.stop'),
            'startup' => Arr::get($parsed, 'startup'),
            'script_install' => Arr::get($parsed, 'scripts.installation.script'),
            'script_entry' => Arr::get($parsed, 'scripts.installation.entrypoint'),
            'script_container' => Arr::get($parsed, 'scripts.installation.container'),
        ]);
    }

    /**
     * Converts a PTDL_V1 egg into the expected PTDL_V2 egg format. This just handles
     * the "docker_images" field potentially not being present, and not being in the
     * expected "key => value" format.
     */
    protected function convertToV2(array $parsed): array
    {
        if (Arr::get($parsed, 'meta.version') === Egg::EXPORT_VERSION) {
            return $parsed;
        }

        // Maintain backwards compatability for eggs that are still using the old single image
        // string format. New eggs can provide an array of Docker images that can be used.
        if (!isset($parsed['images'])) {
            $images = [Arr::get($parsed, 'image') ?? 'nil'];
        } else {
            $images = $parsed['images'];
        }

        unset($parsed['images'], $parsed['image']);

        $parsed['docker_images'] = [];
        foreach ($images as $image) {
            $parsed['docker_images'][$image] = $image;
        }

        $parsed['variables'] = array_map(function ($value) {
            return array_merge($value, ['field_type' => 'text']);
        }, $parsed['variables']);

        return $parsed;
    }
}
