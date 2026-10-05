<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\CopiesFiles;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class CopyFile implements CopiesFiles
{
    public function copy(Server $server, string $location): void
    {
        Daemon::server($server)->files()->copyFile($location);
    }
}
