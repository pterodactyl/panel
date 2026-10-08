<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Contracts\Servers\AttachesMountsToServers;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\MountServer;
use Pterodactyl\Models\Server;

final class AttachMountToServer implements AttachesMountsToServers
{
    public function attach(Server $server, int $mountId): Mount
    {
        $mount = Mount::query()->availableToServer($server)->whereKey($mountId)->first();
        if ($mount === null) {
            throw (new ModelNotFoundException)->setModel(Mount::class);
        }

        (new MountServer)->forceFill(['mount_id' => $mount->id, 'server_id' => $server->id])->saveOrFail();

        return $mount;
    }
}
