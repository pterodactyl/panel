<?php

namespace Pterodactyl\Services\Allocations;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Allocation\AllocationDoesNotBelongToServerException;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonRepositoryInterface;

class SetDefaultAllocationService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    private $daemonRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $serverRepository;

    /**
     * SetDefaultAllocationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface    $repository
     * @param \Illuminate\Database\ConnectionInterface                           $connection
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonRepository
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface        $serverRepository
     */
    public function __construct(
        AllocationRepositoryInterface $repository,
        ConnectionInterface $connection,
        DaemonRepositoryInterface $daemonRepository,
        ServerRepositoryInterface $serverRepository
    ) {
        $this->connection = $connection;
        $this->daemonRepository = $daemonRepository;
        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
    }

    /**
     * Update the default allocation for a server only if that allocation is currently
     * assigned to the specified server.
     *
     * @param int|\Pterodactyl\Models\Server $server
     * @param int                            $allocation
     * @return \Pterodactyl\Models\Allocation
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\AllocationDoesNotBelongToServerException
     */
    public function handle($server, int $allocation): Allocation
    {
        if (! $server instanceof Server) {
            $server = $this->serverRepository->find($server);
        }

        $allocations = $this->repository->findWhere([['server_id', '=', $server->id]]);
        $model = $allocations->filter(function ($model) use ($allocation) {
            return $model->id === $allocation;
        })->first();

        if (! $model instanceof Allocation) {
            throw new AllocationDoesNotBelongToServerException;
        }

        $this->connection->beginTransaction();
        $this->serverRepository->withoutFreshModel()->update($server->id, ['allocation_id' => $model->id]);

        // Update on the daemon.
        try {
            $this->daemonRepository->setServer($server)->update([
                'build' => [
                    'default' => [
                        'ip' => $model->ip,
                        'port' => $model->port,
                    ],
                    'ports|overwrite' => $allocations->groupBy('ip')->map(function ($item) {
                        return $item->pluck('port');
                    })->toArray(),
                ],
            ]);

            $this->connection->commit();
        } catch (RequestException $exception) {
            $this->connection->rollBack();
            throw new DaemonConnectionException($exception);
        }

        return $model;
    }
}
