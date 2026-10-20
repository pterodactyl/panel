<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Users;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Actions\Users\ReplaceUserAvatar;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use UnexpectedValueException;

class UpdateAvatarRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminUsersUpdate];
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return ReplaceUserAvatar::rules();
    }

    public function avatar(): UploadedFile
    {
        $file = $this->file('avatar');
        throw_unless($file instanceof UploadedFile, UnexpectedValueException::class, 'Expected an uploaded avatar.');

        return $file;
    }
}
