<?php

namespace Pterodactyl\Jobs\Server;

use Pterodactyl\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;

class DeleteServerJob extends Job implements ShouldQueue
{
    use DispatchesJobs;
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * @var \Pterodactyl\Models\Server
     */
    public Server $server;

    /**
     * DeleteServerJob constructor.
     */
    public function __construct(Server $server)
    {
        $this->queue = config('pterodactyl.queues.standard');
        $this->server = $server;
    }

    /**
     * Delete the server.
     *
     * @throws \Throwable
     */
    public function handle(DaemonServerRepository $daemonServerRepository)
    {
        $daemonServerRepository->setServer($this->server)->delete();
        $this->server->delete();
    }
}
