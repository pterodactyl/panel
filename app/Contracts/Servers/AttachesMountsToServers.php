<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Server;

interface AttachesMountsToServers
{
    public function attach(Server $server, int $mountId): Mount;
}
