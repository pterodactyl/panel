<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Models\User;

interface UpdatesUserEmails
{
    /**
     * @return array{user: User, original: string, email: string, changed: bool}
     */
    public function update(User $user, string $email): array;
}
