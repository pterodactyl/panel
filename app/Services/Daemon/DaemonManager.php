<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Daemon;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;

final class DaemonManager
{
    public function server(Server $server, ?Node $node = null): ServerClient
    {
        return ServerClient::forServer($server, $node);
    }

    public function node(Node $node): NodeClient
    {
        return NodeClient::forNode($node);
    }
}
