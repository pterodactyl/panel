<?php

namespace Pterodactyl\Events\Subuser;

use Pterodactyl\Events\Event;
use Pterodactyl\Models\Subuser;
use Illuminate\Queue\SerializesModels;

class Created extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Subuser $subuser)
    {
    }
}
