<?php

namespace Pterodactyl\Events\Auth;

use Pterodactyl\Models\User;

class ProvidedAuthenticationToken
{
    public User $user;

    public bool $recovery;

    public function __construct(User $user, bool $recovery = false)
    {
        $this->user = $user;
        $this->recovery = $recovery;
    }
}
