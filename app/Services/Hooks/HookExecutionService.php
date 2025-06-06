<?php

namespace Pterodactyl\Services\Hooks;


use Pterodactyl\Models\Hook;

class HookExecutionService
{
    /**
     * HookExecutionService constructor.
     */
    public function __construct() {}

    /**
     * Execute a hook
     *
     */
    public function handle(array $data): Hook
    {
        return $this->repository->create($data);
    }
}
