<?php

namespace Pterodactyl\Events\Auth;

use Pterodactyl\Events\Event;
use Illuminate\Queue\SerializesModels;

class FailedCaptcha extends Event
{
    use SerializesModels;

    /**
     * The IP that the request originated from.
     */
    public string $ip;

    /**
     * The domain that was used to try to verify the request with recaptcha api.
     */
    public string $domain;

    /**
     * Create a new event instance.
     */
    public function __construct(string $ip, string $domain)
    {
        $this->ip = $ip;
        $this->domain = $domain;
    }
}
