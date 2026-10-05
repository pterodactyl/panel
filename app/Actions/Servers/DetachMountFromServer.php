<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Pterodactyl\Contracts\Servers\DetachesMountsFromServers;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\MountServer;
use Pterodactyl\Models\Server;

final readonly class DetachMountFromServer implements DetachesMountsFromServers
{
    public function detach(Server $server, Mount $mount): void
    {
        MountServer::query()->where('mount_id', $mount->id)->where('server_id', $server->id)->delete();
    }
}
