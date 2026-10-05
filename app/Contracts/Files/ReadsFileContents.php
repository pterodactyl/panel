<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Files;

use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException;
use Pterodactyl\Models\Server;

interface ReadsFileContents
{
    /**
     * Read a server file through Wings. The read is always bounded: without an
     * explicit limit the panel's configured maximum editable file size applies.
     *
     * @throws FileSizeTooLargeException
     * @throws DaemonConnectionException
     */
    public function read(Server $server, string $path, ?int $maxBytes = null): string;
}
