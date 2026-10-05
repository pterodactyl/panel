<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Throwable;

interface ChangesServerEgg
{
    /**
     * Move a server onto another egg. The startup command and Docker image are
     * reset to the new egg's defaults and the variable values stored for the
     * previous egg are discarded. With $keepVariables, values whose environment
     * name also exists on the new egg are carried over when they still satisfy
     * the new variable's rules.
     *
     * @throws Throwable
     */
    public function change(Server $server, Egg $egg, bool $keepVariables = false): Server;
}
