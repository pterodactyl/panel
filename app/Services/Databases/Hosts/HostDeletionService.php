<?php

namespace App\Services\Databases\Hosts;

use App\Exceptions\Service\HasActiveServersException;
use App\Contracts\Repository\DatabaseRepositoryInterface;
use App\Contracts\Repository\DatabaseHostRepositoryInterface;

class HostDeletionService
{
    /**
     * @var \App\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $databaseRepository;

    /**
     * @var \App\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    private $repository;

    /**
     * HostDeletionService constructor.
     *
     * @param \App\Contracts\Repository\DatabaseRepositoryInterface     $databaseRepository
     * @param \App\Contracts\Repository\DatabaseHostRepositoryInterface $repository
     */
    public function __construct(
        DatabaseRepositoryInterface $databaseRepository,
        DatabaseHostRepositoryInterface $repository
    ) {
        $this->databaseRepository = $databaseRepository;
        $this->repository = $repository;
    }

    /**
     * Delete a specified host from the Panel if no databases are
     * attached to it.
     *
     * @param int $host
     * @return int
     *
     * @throws \App\Exceptions\Service\HasActiveServersException
     */
    public function handle(int $host): int
    {
        $count = $this->databaseRepository->findCountWhere([['database_host_id', '=', $host]]);
        if ($count > 0) {
            throw new HasActiveServersException(trans('exceptions.databases.delete_has_databases'));
        }

        return $this->repository->delete($host);
    }
}
