<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Locations;

use App\Contracts\Repository\LocationRepositoryInterface;

class LocationCreationService
{
    /**
     * @var \App\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * LocationCreationService constructor.
     *
     * @param \App\Contracts\Repository\LocationRepositoryInterface $repository
     */
    public function __construct(LocationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new location.
     *
     * @param array $data
     * @return \App\Models\Location
     *
     * @throws \App\Exceptions\Model\DataValidationException
     */
    public function handle(array $data)
    {
        return $this->repository->create($data);
    }
}
