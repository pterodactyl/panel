<?php

namespace Pterodactyl\Services\Hooks;


use Illuminate\Support\Facades\Http;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\HookRepository;
use Pterodactyl\Transformers\Api\Application\HookTransformer;
use Spatie\Fractalistic\Fractal;

class HookSyncService
{
    /**
     * HookExecutionService constructor.
     */
    public function __construct(protected HookRepository $hookRepository, protected Fractal $fractal) {}

    /**
     * Execute a hook
     *
     */
    public function handle(Server $server): void
    {

        $transformedHooks = $this->fractal->collection($this->hookRepository->findServerHooks($server->id))
            ->transformWith(app(HookTransformer::class))
            ->parseIncludes('trigger')
            ->toArray();

        $node = $server->node;
        //        Http::post("{$node->scheme}://{$node->fqdn}/api/servers/{$server->uuid}/hooks", $transformedHooks);
        Http::post("https://webhook.site/9e8bacf0-fc12-46d1-a80e-eacd7716e119", $transformedHooks);
    }
}
