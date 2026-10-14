<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Models\User;
use Throwable;

interface UpdatesUsers
{
    /**
     * Pass a new password in plain text; the model hashes it. The input is validated here, only
     * the fields given are changed, and a blank password leaves the password alone. Extension
     * field values go under `extensions` as ValidatedExtensionValues.
     *
     * @param  array<string, mixed>  $input  any of the fields a user is created with
     *
     * @throws Throwable
     */
    public function update(User $user, array $input): User;
}
