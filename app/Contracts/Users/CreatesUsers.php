<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Models\User;
use Throwable;

interface CreatesUsers
{
    /**
     * A user given no password gets a random one and a password reset token for the welcome
     * email. Pass a password in plain text; the model hashes it. The input is validated here.
     * Extension field values go under `extensions` as ValidatedExtensionValues.
     *
     * @param  array<string, mixed>  $input  email, username, name_first, name_last, and optionally external_id, password, root_admin and language
     *
     * @throws Throwable
     */
    public function create(array $input): User;
}
