<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Server;

interface DetachesMountsFromServers
{
    public function detach(Server $server, Mount $mount): void;
}
