<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Models\Server;

interface RebuildsServers
{
    public function rebuild(Server $server): void;
}
