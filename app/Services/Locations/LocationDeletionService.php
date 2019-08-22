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
use Webmozart\Assert\Assert;
use App\Contracts\Repository\NodeRepositoryInterface;
use App\Contracts\Repository\LocationRepositoryInterface;
use App\Exceptions\Service\Location\HasActiveNodesException;

class LocationDeletionService
{
    /**
     * @var \App\Contracts\Repository\NodeRepositoryInterface
     */
    protected $nodeRepository;

    /**
     * @var \App\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * LocationDeletionService constructor.
     *
     * @param \App\Contracts\Repository\LocationRepositoryInterface $repository
     * @param \App\Contracts\Repository\NodeRepositoryInterface     $nodeRepository
     */
    public function __construct(
        LocationRepositoryInterface $repository,
        NodeRepositoryInterface $nodeRepository
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->repository = $repository;
    }

    /**
     * Delete an existing location.
     *
     * @param int|\App\Models\Location $location
     * @return int|null
     *
     * @throws \App\Exceptions\Service\Location\HasActiveNodesException
     */
    public function handle($location)
    {
        $location = ($location instanceof Location) ? $location->id : $location;

        Assert::integerish($location, 'First argument passed to handle must be numeric or an instance of ' . Location::class . ', received %s.');

        $count = $this->nodeRepository->findCountWhere([['location_id', '=', $location]]);
        if ($count > 0) {
            throw new HasActiveNodesException(trans('exceptions.locations.has_nodes'));
        }

        return $this->repository->delete($location);
    }
}
