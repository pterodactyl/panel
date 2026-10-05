<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Extensions;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;
use UnexpectedValueException;

class UploadExtensionSettingFileRequest extends UpdateExtensionRequest
{
    /**
     * The setting's own size limit and type allow-list are enforced against the
     * file's content when it is stored; this is only the outer bound.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return ['file' => ['required', 'file', 'max:'.ExtensionSettingFiles::MAX_KILOBYTES]];
    }

    public function upload(): UploadedFile
    {
        $file = $this->file('file');
        throw_unless($file instanceof UploadedFile, UnexpectedValueException::class, 'Expected an uploaded settings file.');

        return $file;
    }
}
