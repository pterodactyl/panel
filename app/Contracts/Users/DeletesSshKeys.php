<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Models\UserSSHKey;

interface DeletesSshKeys
{
    public function delete(UserSSHKey $key): void;
}
