<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Models\Server;

interface SendsServerPower
{
    public function send(Server $server, string $signal): void;
}
