<?php

namespace Pterodactyl\Events\Subuser;

use Pterodactyl\Events\Event;
use Pterodactyl\Models\Subuser;
use Illuminate\Queue\SerializesModels;

class Creating extends Event
{
    use SerializesModels;

    /**
     * The Eloquent model of the server.
     */
    public Subuser $subuser;

    /**
     * Create a new event instance.
     */
    public function __construct(Subuser $subuser)
    {
        $this->subuser = $subuser;
    }
}
