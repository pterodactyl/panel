<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Locations;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Location;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Exceptions\Service\Location\HasActiveNodesException;

class LocationDeletionService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $nodeRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * LocationDeletionService constructor.
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
     * @param int|\Pterodactyl\Models\Location $location
     *
     * @return int|null
     *
     * @throws \Pterodactyl\Exceptions\Service\Location\HasActiveNodesException
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
