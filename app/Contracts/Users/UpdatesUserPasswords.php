<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Models\User;

interface UpdatesUserPasswords
{
    /** Every other session and "remember me" login made with the old password is ended. */
    public function update(User $user, string $password): User;
}
