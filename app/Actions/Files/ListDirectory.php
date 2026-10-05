<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\ListsDirectories;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class ListDirectory implements ListsDirectories
{
    public function list(Server $server, string $directory = '/'): array
    {
        return Daemon::server($server)->files()->getDirectory($directory);
    }
}
