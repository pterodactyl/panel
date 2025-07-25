<?php

namespace Pterodactyl\Events\Auth;

use Pterodactyl\Models\User;
use Pterodactyl\Events\Event;

class ProvidedAuthenticationToken extends Event
{
    public function __construct(public User $user, public bool $recovery = false)
    {
    }
}
