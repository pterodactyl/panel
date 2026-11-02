<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Contracts\Users\ReplacesUserAvatars;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;
use Throwable;

final readonly class ReplaceUserAvatar implements ReplacesUserAvatars
{
    private const array TYPES = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp', 'image/avif', 'image/x-icon'];

    public function __construct(private ExtensionSettingFiles $files) {}

    /**
     * The outer bound of an upload; its type and size are checked against its content when it is stored.
     *
     * @return ValidationRules
     */
    public static function rules(): array
    {
        return ['avatar' => ['required', 'file', 'max:'.ExtensionSettingFiles::MAX_KILOBYTES]];
    }

    public function replace(User $user, ?UploadedFile $avatar): User
    {
        $previous = $user->avatar;
        $name = $avatar instanceof UploadedFile ? $this->files->store(User::AVATAR_FILES, $avatar, self::TYPES, ExtensionSettingFiles::MAX_KILOBYTES) : null;

        try {
            $user->forceFill(['avatar' => $name])->saveOrFail();
        } catch (Throwable $throwable) {
            if ($name !== null) {
                rescue(fn () => $this->files->delete(User::AVATAR_FILES, $name));
            }

            throw $throwable;
        }

        if ($previous !== null) {
            rescue(fn () => $this->files->delete(User::AVATAR_FILES, $previous));
        }

        return $user;
    }
}
