<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Servers;

use Pterodactyl\Models\Server;

interface TogglesServerSuspension
{
    public const string ACTION_SUSPEND = 'suspend';

    public const string ACTION_UNSUSPEND = 'unsuspend';

    public function toggle(Server $server, string $action = self::ACTION_SUSPEND): void;
}
