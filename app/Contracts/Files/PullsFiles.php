<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Files;

use Pterodactyl\Models\Server;

interface PullsFiles
{
    /** @param  array{filename?: string|null, use_header?: bool, foreground?: bool, timeout?: int}  $options */
    public function pull(Server $server, string $url, ?string $directory, array $options): void;
}
