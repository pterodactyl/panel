<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Support\Facades\Cache;
use Pterodactyl\Contracts\Servers\ReadsServerState;
use Pterodactyl\Data\ServerState;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class ReadServerState implements ReadsServerState
{
    private const int CACHE_SECONDS = 20;

    public function read(Server $server): ServerState
    {
        $stats = Cache::remember(
            "resources:$server->uuid",
            now()->addSeconds(self::CACHE_SECONDS),
            fn (): array => Daemon::server($server)->details(),
        );

        return ServerState::fromDaemon($stats);
    }
}
