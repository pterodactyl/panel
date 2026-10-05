<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Models\Server;
use Throwable;

interface ReinstallsServers
{
    /** @throws Throwable */
    public function reinstall(Server $server): Server;
}
