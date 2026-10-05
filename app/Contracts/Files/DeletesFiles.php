<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Files;

use Pterodactyl\Models\Server;

interface DeletesFiles
{
    /** @param array<array-key, ApiValue9> $files */
    public function delete(Server $server, ?string $root, array $files): void;
}
