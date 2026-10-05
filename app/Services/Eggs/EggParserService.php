<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Eggs;

use Illuminate\Http\UploadedFile;
use JsonException;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Models\Egg;

class EggParserService
{
    /**
     * Takes an uploaded file and parses out the egg configuration from within.
     *
     * @throws JsonException
     * @throws InvalidFileUploadException
     */
    public function handle(UploadedFile $file): EggImportData
    {
        throw_if($file->getError() !== UPLOAD_ERR_OK || ! $file->isFile(), InvalidFileUploadException::class, 'The selected file is not valid and cannot be imported.');

        $contents = $file->openFile()->fread($file->getSize());
        throw_if($contents === false, InvalidFileUploadException::class, 'The selected file could not be read.');

        return EggImportData::fromJson($contents);
    }

    /**
     * Fills the provided model with the parsed JSON data.
     */
    public function fillFromParsed(Egg $model, EggImportData $parsed): Egg
    {
        return $model->forceFill([
            'name' => $parsed->name,
            'description' => $parsed->description,
            'features' => $parsed->features,
            'docker_images' => $parsed->dockerImages,
            'file_denylist' => $parsed->fileDenylist,
            'update_url' => $parsed->updateUrl,
            'config_files' => $parsed->configFiles,
            'config_startup' => $parsed->configStartup,
            'config_logs' => $parsed->configLogs,
            'config_stop' => $parsed->configStop,
            'startup' => $parsed->startup,
            'script_install' => $parsed->scriptInstall,
            'script_entry' => $parsed->scriptEntry,
            'script_container' => $parsed->scriptContainer,
        ]);
    }
}
