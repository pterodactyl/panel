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
     * The input is validated here, so it can come straight from any caller. Only the fields
     * given are changed; a blank password leaves the password alone. Extension field values
     * go under `extensions` as ValidatedExtensionValues.
     *
     * @param  ActionInput  $input  any of the fields a user is created with
     *
     * @throws Throwable
     */
    public function update(User $user, array $input): User;
}
