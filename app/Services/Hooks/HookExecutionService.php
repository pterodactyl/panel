<?php

namespace Pterodactyl\Services\Hooks;


use Pterodactyl\Jobs\Hook\ExecuteHookActionJob;
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
    public function handle(Hook $hook): void
    {
        ExecuteHookActionJob::dispatch($hook);
    }
}
