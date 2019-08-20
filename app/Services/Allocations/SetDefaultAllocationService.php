<?php

namespace App\Services\Allocations;

use App\Models\Server;
use App\Models\Allocation;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use App\Contracts\Repository\ServerRepositoryInterface;
use App\Contracts\Repository\AllocationRepositoryInterface;
use App\Exceptions\Http\Connection\DaemonConnectionException;
use App\Exceptions\Service\Allocation\AllocationDoesNotBelongToServerException;
use App\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonRepositoryInterface;

class SetDefaultAllocationService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \App\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    private $daemonRepository;

    /**
     * @var \App\Contracts\Repository\AllocationRepositoryInterface
     */
    private $repository;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    private $serverRepository;

    /**
     * SetDefaultAllocationService constructor.
     *
     * @param \App\Contracts\Repository\AllocationRepositoryInterface    $repository
     * @param \Illuminate\Database\ConnectionInterface                           $connection
     * @param \App\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonRepository
     * @param \App\Contracts\Repository\ServerRepositoryInterface        $serverRepository
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
     * @param int|\App\Models\Server $server
     * @param int                            $allocation
     * @return \App\Models\Allocation
     *
     * @throws \App\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Allocation\AllocationDoesNotBelongToServerException
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
