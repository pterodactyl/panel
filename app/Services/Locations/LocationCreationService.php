<?php

namespace Pterodactyl\Services\Locations;

use Pterodactyl\Models\Location;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class LocationCreationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected LocationRepositoryInterface $repository;

    /**
     * LocationCreationService constructor.
     */
    public function __construct(LocationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new location.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data): Location
    {
        return $this->repository->create($data);
    }
}
