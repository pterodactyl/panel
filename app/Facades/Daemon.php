<?php

declare(strict_types=1);

namespace Pterodactyl\Facades;

use Illuminate\Support\Facades\Facade;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Daemon\DaemonManager;
use Pterodactyl\Services\Daemon\NodeClient;
use Pterodactyl\Services\Daemon\ServerClient;

/**
 * @method static ServerClient server(Server $server, Node|null $node = null)
 * @method static NodeClient node(Node $node)
 *
 * @see DaemonManager
 */
class Daemon extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DaemonManager::class;
    }
}
