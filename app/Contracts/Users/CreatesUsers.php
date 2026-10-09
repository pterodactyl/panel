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
     * @param  UserCreationData  $data
     *
     * @throws Throwable
     */
    public function create(array $data): User;
}
