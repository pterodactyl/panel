<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Settings;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;
use UnexpectedValueException;

class UploadLogoRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminSettingsUpdate];
    }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'max:'.ExtensionSettingFiles::MAX_KILOBYTES]];
    }

    public function upload(): UploadedFile
    {
        $file = $this->file('file');
        throw_unless($file instanceof UploadedFile, UnexpectedValueException::class, 'Expected an uploaded logo.');

        return $file;
    }
}
