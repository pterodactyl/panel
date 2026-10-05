<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Pterodactyl\Contracts\Servers\RebuildsServers;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class RebuildServer implements RebuildsServers
{
    public function rebuild(Server $server): void
    {
        Daemon::server($server)->sync();
    }
}
