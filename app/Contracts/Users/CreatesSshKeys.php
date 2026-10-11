<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSSHKey;

interface CreatesSshKeys
{
    /**
     * Add an SSH public key to a user's account for SFTP. The key is not parsed here, so
     * pass one that is already validated along with its fingerprint.
     */
    public function create(User $user, string $name, string $publicKey, string $fingerprint): UserSSHKey;
}
