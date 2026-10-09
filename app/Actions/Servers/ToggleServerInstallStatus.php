<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Pterodactyl\Contracts\Servers\TogglesServerInstallStatus;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Server;

final readonly class ToggleServerInstallStatus implements TogglesServerInstallStatus
{
    /**
     * Flip a server between installed and installing. A server whose install
     * failed must be reinstalled instead.
     *
     * @throws DisplayException
     */
    public function toggle(Server $server): void
    {
        if ($server->status === Server::STATUS_INSTALL_FAILED) {
            throw new DisplayException(trans('admin/server.exceptions.marked_as_failed'));
        }

        $server->forceFill([
            'status' => $server->isInstalled() ? Server::STATUS_INSTALLING : null,
        ])->saveOrFail();
    }
}
