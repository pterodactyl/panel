<?php

namespace Pterodactyl\Services\Servers;

use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class SuspensionService
{
    const ACTION_SUSPEND = 'suspend';
    const ACTION_UNSUSPEND = 'unsuspend';

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $writer;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * SuspensionService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Repositories\Wings\DaemonServerRepository $daemonServerRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     * @param \Psr\Log\LoggerInterface $writer
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonServerRepository $daemonServerRepository,
        ServerRepositoryInterface $repository,
        LoggerInterface $writer
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->writer = $writer;
        $this->daemonServerRepository = $daemonServerRepository;
    }

    /**
     * Suspends a server on the system.
     *
     * @param int|\Pterodactyl\Models\Server $server
     * @param string $action
     *
     * @throws \Throwable
     */
    public function toggle(Server $server, $action = self::ACTION_SUSPEND)
    {
        Assert::oneOf($action, [self::ACTION_SUSPEND, self::ACTION_UNSUSPEND]);

        if (
            $action === self::ACTION_SUSPEND && $server->suspended ||
            $action === self::ACTION_UNSUSPEND && ! $server->suspended
        ) {
            return;
        }

        $this->connection->transaction(function () use ($action, $server) {
            $this->repository->withoutFreshModel()->update($server->id, [
                'suspended' => $action === self::ACTION_SUSPEND,
            ]);

            $this->daemonServerRepository->setServer($server)->suspend($action === self::ACTION_UNSUSPEND);
        });
    }
}
