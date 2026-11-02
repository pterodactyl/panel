<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Actions\Users\ReplaceUserAvatar;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use UnexpectedValueException;

class UpdateAvatarRequest extends ClientApiRequest
{
    /**
     * Users may only upload their own avatar while the panel allows it.
     */
    public function authorize(): bool
    {
        return config()->boolean('pterodactyl.client_features.avatars.enabled') && parent::authorize();
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
