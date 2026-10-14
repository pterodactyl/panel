<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Files;

use Pterodactyl\Models\Server;

interface RenamesFiles
{
    /** @param array<mixed> $files */
    public function rename(Server $server, ?string $root, array $files): void;
}
