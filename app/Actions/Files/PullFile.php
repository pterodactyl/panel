<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Files;

use Pterodactyl\Contracts\Files\PullsFiles;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class PullFile implements PullsFiles
{
    /** @param array<array-key, ApiValue9> $options */
    public function pull(Server $server, string $url, ?string $directory, array $options): void
    {
        Daemon::server($server)->files()->pull($url, $directory, $options);
    }
}
