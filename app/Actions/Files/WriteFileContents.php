<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\WritesFileContents;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class WriteFileContents implements WritesFileContents
{
    public function write(Server $server, string $path, string $contents): void
    {
        Daemon::server($server)->files()->putContent($path, $contents);
    }
}
