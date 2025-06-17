<?php

namespace Pterodactyl\Services\Hooks;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\HookRepository;
use Pterodactyl\Repositories\Wings\DaemonHookRepository;

class HookSyncService
{
    /**
     * HookExecutionService constructor.
     */
    public function __construct(protected HookRepository $hookRepository, protected DaemonHookRepository $daemonHookRepository) {}

    /**
     * Execute a hook
     *
     * @throws DaemonConnectionException
     */
    public function handle(Server $server): void
    {
        $hooks = $this->hookRepository->findServerHooks($server->id)
            ->map(function ($hook) {
                return [
                    'id'      => $hook->id,
                    'name'    => $hook->name,
                    'enabled' => $hook->enabled,
                    'trigger' => $hook->trigger,
                ];
            });

        $this->daemonHookRepository->setServer($server)->send($hooks);
    }
}
