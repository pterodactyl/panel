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
     *
     * @param \Pterodactyl\Contracts\Repository\LocationRepositoryInterface $repository
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface     $nodeRepository
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
     * @param int $location
     * @return int|null
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Location\HasActiveNodesException
     */
    public function handle($location)
    {
        Assert::integerish($location, 'First argument passed to handle must be numeric, received %s.');

        $count = $this->nodeRepository->findCountWhere([['location_id', '=', $location]]);
        if ($count > 0) {
            throw new HasActiveNodesException(trans('exceptions.locations.has_nodes'));
        }

        return $this->repository->delete($location);
    }
}
