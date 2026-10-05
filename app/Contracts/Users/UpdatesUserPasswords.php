<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Models\User;

interface UpdatesUserPasswords
{
    public function update(User $user, string $password): User;
}
