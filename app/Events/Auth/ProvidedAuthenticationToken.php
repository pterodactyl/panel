<?php

declare(strict_types=1);

namespace Pterodactyl\Events\Auth;

use Pterodactyl\Events\Event;
use Pterodactyl\Models\User;

class ProvidedAuthenticationToken extends Event
{
    public function __construct(public User $user, public bool $recovery = false) {}
}
