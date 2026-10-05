<?php

declare(strict_types=1);

namespace Pterodactyl\Events\User;

use Illuminate\Queue\SerializesModels;
use Pterodactyl\Events\Event;
use Pterodactyl\Models\User;

class Created extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public User $user) {}
}
