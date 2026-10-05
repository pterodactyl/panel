<?php

declare(strict_types=1);

namespace Pterodactyl\Events\Subuser;

use Illuminate\Queue\SerializesModels;
use Pterodactyl\Events\Event;
use Pterodactyl\Models\Subuser;

class Deleted extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Subuser $subuser) {}
}
