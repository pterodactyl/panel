<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Files;

use Pterodactyl\Models\Server;

interface PullsFiles
{
    /** @param array<array-key, ApiValue9> $options */
    public function pull(Server $server, string $url, ?string $directory, array $options): void;
}
