<?php

namespace Pterodactyl\Services\Servers;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Exceptions\Http\Server\ServerTransferringException;

class SuspensionService
{
    public const ACTION_SUSPEND = 'suspend';
    public const ACTION_UNSUSPEND = 'unsuspend';

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
        if ($isSuspending === $server->isSuspended()) {
            return;
        }

        // Check if the server is currently being transferred.
        if (!is_null($server->transfer)) {
            throw new ServerTransferringException();
        }

        $this->connection->transaction(function () use ($action, $server, $isSuspending) {
            $server->update([
                'status' => $isSuspending ? Server::STATUS_SUSPENDED : null,
            ]);

            // Only send the suspension request to wings if the server is not currently being transferred.
            if (is_null($server->transfer)) {
                $this->daemonServerRepository->setServer($server)->suspend($action === self::ACTION_UNSUSPEND);
            }
        });
    }
}
