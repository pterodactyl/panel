<?php

namespace App\Services\Allocations;

use App\Models\Allocation;
use App\Contracts\Repository\AllocationRepositoryInterface;
use App\Exceptions\Service\Allocation\ServerUsingAllocationException;

class AllocationDeletionService
{
    /**
     * @var \App\Contracts\Repository\AllocationRepositoryInterface
     */
    private $repository;

    /**
     * AllocationDeletionService constructor.
     *
     * @param \App\Contracts\Repository\AllocationRepositoryInterface $repository
     */
    public function __construct(AllocationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Delete an allocation from the database only if it does not have a server
     * that is actively attached to it.
     *
     * @param \App\Models\Allocation $allocation
     * @return int
     *
     * @throws \App\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function handle(Allocation $allocation)
    {
        if (! is_null($allocation->server_id)) {
            throw new ServerUsingAllocationException(trans('exceptions.allocations.server_using'));
        }

        return $this->repository->delete($allocation->id);
    }
}
