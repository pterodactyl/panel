<?php

namespace Pterodactyl\Events\Auth;

use Pterodactyl\Models\User;
use Pterodactyl\Events\Event;

class ProvidedAuthenticationToken extends Event
{
    public User $user;

    public bool $recovery;

    public function __construct(User $user, bool $recovery = false)
    {
        $this->user = $user;
        $this->recovery = $recovery;
    }
}
