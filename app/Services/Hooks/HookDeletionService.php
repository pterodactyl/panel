<?php

namespace Pterodactyl\Services\Hooks;

use Pterodactyl\Models\Hook;
use Pterodactyl\Models\Location;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Repositories\Eloquent\HookRepository;

class HookDeletionService
{
    /**
     * HookDeletionService constructor.
     */
    public function __construct(private HookRepository $repository) {}

    /**
     * Delete's a hook
     *
     */
    public function handle(Hook $hook): void
    {
       $this->repository->delete($hook->id);
    }
}
