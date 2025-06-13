<?php

namespace Pterodactyl\Services\Hooks;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\HookRepository;

class HookSyncService
{
    /**
     * HookExecutionService constructor.
     */
    public function __construct(protected HookRepository $hookRepository) {}

    /**
     * Execute a hook
     *
     */
    public function handle(Server $server): void
    {
        $hooks = $this->hookRepository->findServerHooks($server->id);

        $node = $server->node;

        Http::post("{$node->scheme}://{$node->fqdn}/api/servers/{$server->uuid}/hooks", $hooks);
    }
}
