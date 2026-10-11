<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Pterodactyl\Contracts\Users\DeletesSshKeys;
use Pterodactyl\Models\UserSSHKey;

final readonly class DeleteSshKey implements DeletesSshKeys
{
    public function delete(UserSSHKey $key): void
    {
        $key->delete();
    }
}
