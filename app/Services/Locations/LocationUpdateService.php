<?php

namespace Pterodactyl\Services\Locations;

use Pterodactyl\Models\Location;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class LocationUpdateService
{
    protected LocationRepositoryInterface $repository;

    /**
     * LocationUpdateService constructor.
     */
    public function __construct(LocationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update an existing location.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Location|int $location, array $data): Location
    {
        $location = ($location instanceof Location) ? $location->id : $location;

        return $this->repository->update($location, $data);
    }
}
