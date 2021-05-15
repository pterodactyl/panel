<?php

namespace Pterodactyl\Services\Allocations;

use Pterodactyl\Models\Allocation;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException;

class AllocationCreationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    private $repository;

    /**
     * AllocationCreationService constructor.
     */
    public function __construct(AllocationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new allocation.
     *
     * @return \Pterodactyl\Models\Allocation
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data)
    {
        return $this->repository->create($data);
    }
}