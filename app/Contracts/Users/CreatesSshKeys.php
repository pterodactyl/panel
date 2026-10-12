<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Illuminate\Validation\ValidationException;
use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSSHKey;

interface CreatesSshKeys
{
    /**
     * The key is not parsed here, so pass one that is already validated along with its
     * fingerprint. The name, key and fingerprint are validated here: none may be blank.
     *
     * @throws ValidationException
     */
    public function create(User $user, string $name, string $publicKey, string $fingerprint): UserSSHKey;
}
