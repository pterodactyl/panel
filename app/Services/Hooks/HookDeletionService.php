<?php

namespace Pterodactyl\Services\Hooks;

use Pterodactyl\Models\Hook;
use Pterodactyl\Models\Location;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class HookDeletionService
{
    /**
     * HookDeletionService constructor.
     */
    public function __construct() {}

    /**
     * Delete's a hook
     *
     */
    public function handle(Hook $hook): void
    {
        $hook->delete();
    }
}
