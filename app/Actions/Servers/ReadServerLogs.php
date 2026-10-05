<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Pterodactyl\Contracts\Servers\ReadsServerLogs;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class ReadServerLogs implements ReadsServerLogs
{
    public function read(Server $server, int $lines = self::MAX_LINES): array
    {
        return Daemon::server($server)->logs(max(1, min($lines, self::MAX_LINES)));
    }
}
