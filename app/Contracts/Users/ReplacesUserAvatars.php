<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Illuminate\Http\UploadedFile;
use Pterodactyl\Models\User;

interface ReplacesUserAvatars
{
    /**
     * Stores the upload as the user's avatar, or removes it given null, and deletes the picture it replaces.
     */
    public function replace(User $user, ?UploadedFile $avatar): User;
}
