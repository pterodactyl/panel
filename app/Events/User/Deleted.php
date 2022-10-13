<?php

namespace Pterodactyl\Events\User;

use Pterodactyl\Models\User;
use Pterodactyl\Events\Event;
use Illuminate\Queue\SerializesModels;

class Deleted extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public User $user)
    {
    }
}
