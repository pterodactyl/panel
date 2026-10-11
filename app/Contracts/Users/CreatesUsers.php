<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Models\User;
use Throwable;

interface CreatesUsers
{
    /**
     * Create a new user on the system. When no password is provided a random one is
     * set and a password reset token is generated for the account welcome email.
     *
     * The UUID and password hash are handled by the model itself (HasVersion4Uuids
     * and the hashed password cast).
     *
     * The input is validated here, so it can come straight from any caller; extension
     * field values go under `extensions` as ValidatedExtensionValues.
     *
     * @param  array<string, mixed>  $input  email, username, name_first, name_last, and optionally external_id, password, root_admin and language
     *
     * @throws Throwable
     */
    public function create(array $input): User;
}
