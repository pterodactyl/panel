<?php

declare(strict_types=1);

namespace Pterodactyl\Events\Auth;

use Illuminate\Queue\SerializesModels;
use Pterodactyl\Events\Event;

class FailedPasswordReset extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public string $ip, public string $email) {}
}
