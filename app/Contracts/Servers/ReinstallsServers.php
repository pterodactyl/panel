<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Server;
use Throwable;

interface ReinstallsServers
{
    /**
     * @throws DisplayException when the server skips its install script and is installed
     * @throws Throwable
     */
    public function reinstall(Server $server): Server;
}
