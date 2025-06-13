<?php

namespace Pterodactyl\Jobs\Hook;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Hooks\HookSyncService;

class SyncHooksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     */
    public function __construct(public Server $server){}

    /**
     * Execute the job.
     * @throws \Exception
     */
    public function handle(): void
    {
        $hookSyncService = app(HookSyncService::class);
        $hookSyncService->handle($this->server);
    }
}
