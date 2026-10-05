<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use Pterodactyl\Models\User;

interface DisablesTwoFactor
{
    public function disable(User $user): void;
}
