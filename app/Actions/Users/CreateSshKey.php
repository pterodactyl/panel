<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Pterodactyl\Contracts\Users\CreatesSshKeys;
use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSSHKey;

final readonly class CreateSshKey implements CreatesSshKeys
{
    public function create(User $user, string $name, string $publicKey, string $fingerprint): UserSSHKey
    {
        return $user->sshKeys()->create([
            'name' => $name,
            'public_key' => $publicKey,
            'fingerprint' => $fingerprint,
        ]);
    }
}
