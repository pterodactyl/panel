<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Models\Server;

interface UpdatesServerVariable
{
    /**
     * Store the value a server uses for one of its egg's variables. The value is not
     * checked against the variable's rules or the user's access to it, so authorize
     * and validate it first.
     */
    public function update(Server $server, int $variableId, string $value): void;
}
