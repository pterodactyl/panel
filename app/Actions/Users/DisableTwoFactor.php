<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Pterodactyl\Contracts\Users\DisablesTwoFactor;
use Pterodactyl\Models\User;

final class DisableTwoFactor implements DisablesTwoFactor
{
    public function disable(User $user): void
    {
        $user->update([
            'totp_authenticated_at' => now(),
            'use_totp' => false,
        ]);
    }
}
