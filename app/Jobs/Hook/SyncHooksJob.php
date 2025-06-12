<?php

namespace Pterodactyl\Jobs\Hook;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pterodactyl\Models\Hook;
use Pterodactyl\Models\Server;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
class SyncHooksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Server $server){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {

    }
}
