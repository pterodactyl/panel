<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Settings;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Actions\Settings\ReplaceBrandLogo;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use UnexpectedValueException;

class UploadLogoRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminSettingsUpdate];
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return ReplaceBrandLogo::rules();
    }

    public function logo(): UploadedFile
    {
        $file = $this->file('logo');
        throw_unless($file instanceof UploadedFile, UnexpectedValueException::class, 'Expected an uploaded logo.');

        return $file;
    }
}
