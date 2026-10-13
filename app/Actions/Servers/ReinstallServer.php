<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Servers\ReinstallsServers;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;

final readonly class ReinstallServer implements ReinstallsServers
{
    public function reinstall(Server $server): Server
    {
        throw_unless($server->canBeReinstalled(), DisplayException::class, trans('admin/server.exceptions.skipping_install_script'));

        DB::transaction(function () use ($server): void {
            $server->fill(['status' => Server::STATUS_INSTALLING])->save();
            Daemon::server($server)->reinstall();
        });

        Activity::event('server:reinstall')->subject($server)->log();

        return $server;
    }
}
