<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Files;

use Pterodactyl\Models\Server;

interface ChangesFilePermissions
{
    /** @param array<mixed> $files */
    public function change(Server $server, ?string $root, array $files): void;
}
