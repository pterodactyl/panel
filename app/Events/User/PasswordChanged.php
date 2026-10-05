<?php

declare(strict_types=1);

namespace Pterodactyl\Events\User;

use Illuminate\Foundation\Events\Dispatchable;
use Pterodactyl\Models\User;

final readonly class PasswordChanged
{
    use Dispatchable;

    public function __construct(public User $user) {}
}
