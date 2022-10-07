<?php

namespace Pterodactyl\Events\Auth;

use Pterodactyl\Events\Event;
use Illuminate\Queue\SerializesModels;

class FailedPasswordReset extends Event
{
    use SerializesModels;

    /**
     * The IP that the request originated from.
     */
    public string $ip;

    /**
     * The email address that was used when the reset request failed.
     */
    public string $email;

    /**
     * Create a new event instance.
     */
    public function __construct(string $ip, string $email)
    {
        $this->ip = $ip;
        $this->email = $email;
    }
}
