<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Locations;

use App\Models\Location;
use App\Contracts\Repository\LocationRepositoryInterface;

class LocationUpdateService
{
    /**
     * @var \App\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * LocationUpdateService constructor.
     *
     * @param \App\Contracts\Repository\LocationRepositoryInterface $repository
     */
    public function __construct(LocationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update an existing location.
     *
     * @param int|\App\Models\Location $location
     * @param array                            $data
     * @return \App\Models\Location
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($location, array $data)
    {
        $location = ($location instanceof Location) ? $location->id : $location;

        return $this->repository->update($location, $data);
    }
}
