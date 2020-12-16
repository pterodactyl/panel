<?php

namespace Pterodactyl\Services\Servers;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;

class SuspensionService
{
    const ACTION_SUSPEND = 'suspend';
    const ACTION_UNSUSPEND = 'unsuspend';

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * SuspensionService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Repositories\Wings\DaemonServerRepository $daemonServerRepository
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepository $daemonServerRepository
    ) {
        $this->connection = $connection;
        $this->daemonServerRepository = $daemonServerRepository;
    }

    /**
     * Suspends a server on the system.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param string $action
     *
     * @throws \Throwable
     */
    public function toggle(Server $server, $action = self::ACTION_SUSPEND)
    {
        Assert::oneOf($action, [self::ACTION_SUSPEND, self::ACTION_UNSUSPEND]);

        $isSuspending = $action === self::ACTION_SUSPEND;
        // Nothing needs to happen if we're suspending the server and it is already
        // suspended in the database. Additionally, nothing needs to happen if the server
        // is not suspended and we try to un-suspend the instance.
        if ($isSuspending === $server->suspended) {
            return;
        }

        $this->connection->transaction(function () use ($action, $server) {
            $server->update([
                'suspended' => $action === self::ACTION_SUSPEND,
            ]);

            // Only send the suspension request to wings if the server is not currently being transferred.
            if ($server->transfer === null) {
                $this->daemonServerRepository->setServer($server)->suspend($action === self::ACTION_UNSUSPEND);
            }
        });
    }
}
