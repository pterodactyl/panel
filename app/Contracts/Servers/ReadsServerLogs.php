<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Models\Server;

interface ReadsServerLogs
{
    /** The most console lines Wings returns for a single read. */
    public const int MAX_LINES = 100;

    /**
     * Read the most recent console output of a server, oldest line first. The
     * requested line count is clamped between one and MAX_LINES.
     *
     * @return list<string>
     *
     * @throws DaemonConnectionException
     */
    public function read(Server $server, int $lines = self::MAX_LINES): array;
}
