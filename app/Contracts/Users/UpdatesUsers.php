<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Models\User;
use Throwable;

interface UpdatesUsers
{
    /**
     * Update the user model instance and return the updated model. The hashed
     * password cast on the model takes care of hashing a new password.
     *
     * @param  UserUpdateData  $data
     *
     * @throws Throwable
     */
    public function update(User $user, array $data): User;
}
