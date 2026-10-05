<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Files;

use Pterodactyl\Models\Server;

interface CreatesDirectories
{
    public function create(Server $server, string $name, string $root): void;
}
