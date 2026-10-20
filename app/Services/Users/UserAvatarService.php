<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Users;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;

class UserAvatarService
{
    /** Stored with extension setting files under this id, which no extension may take. */
    public const string FILE_GROUP = 'user-avatars';

    private const int MAX_KILOBYTES = 10240;

    private const array MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/svg+xml',
        'image/webp',
        'image/avif',
        'image/x-icon',
    ];

    public function __construct(private readonly ExtensionSettingFiles $files) {}

    public function replace(User $user, UploadedFile $upload): string
    {
        $previous = $user->avatar;
        $file = $this->files->store(self::FILE_GROUP, $upload, self::MIME_TYPES, self::MAX_KILOBYTES);

        $user->forceFill(['avatar' => $file])->save();

        if ($previous !== null) {
            $this->files->delete(self::FILE_GROUP, $previous);
        }

        return $this->url($file);
    }

    public function clear(User $user): void
    {
        $previous = $user->avatar;
        $user->forceFill(['avatar' => null])->save();

        if ($previous !== null) {
            $this->files->delete(self::FILE_GROUP, $previous);
        }
    }

    public function url(?string $file): ?string
    {
        return $file === null ? null : ExtensionSettingFiles::url(self::FILE_GROUP, $file);
    }
}
