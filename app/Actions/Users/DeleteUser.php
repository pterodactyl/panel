<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Pterodactyl\Contracts\Users\DeletesUsers;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;

final readonly class DeleteUser implements DeletesUsers
{
    public function __construct(private ExtensionSettingFiles $files) {}

    public function delete(User $user): ?bool
    {
        if ($user->servers()->exists()) {
            throw new DisplayException(trans('admin/user.exceptions.user_has_servers'));
        }

        $deleted = $user->delete();

        if ($deleted === true && $user->avatar !== null) {
            rescue(fn () => $this->files->delete(User::AVATAR_FILES, $user->avatar));
        }

        return $deleted;
    }
}
