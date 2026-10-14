<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Pterodactyl\Contracts\Servers\SendsServerPower;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class SendServerPower implements SendsServerPower
{
    public function send(Server $server, string $signal): void
    {
        Daemon::server($server)->power($signal);

        Activity::event(mb_strtolower("server:power.{$signal}"))->subject($server)->log();
    }
}
