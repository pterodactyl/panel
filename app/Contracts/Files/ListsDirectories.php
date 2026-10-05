<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Files;

use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Models\Server;

interface ListsDirectories
{
    /**
     * @return list<DaemonFileObject>
     *
     * @throws DaemonConnectionException
     */
    public function list(Server $server, string $directory = '/'): array;
}
