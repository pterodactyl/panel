<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Servers\ReinstallsServers;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;
use Throwable;

final readonly class ReinstallServer implements ReinstallsServers
{
    /**
     * @throws DisplayException when the server skips its install script and is installed
     * @throws Throwable
     */
    public function reinstall(Server $server): Server
    {
        throw_unless($server->canBeReinstalled(), DisplayException::class, trans('admin/server.exceptions.skipping_install_script'));

        return DB::transaction(function () use ($server): Server {
            $server->fill(['status' => Server::STATUS_INSTALLING])->save();
            Daemon::server($server)->reinstall();

            return $server->refresh();
        });
    }
}
