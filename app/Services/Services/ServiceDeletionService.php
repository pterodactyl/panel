<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services;

use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;

class ServiceDeletionService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface
     */
    protected $repository;

    /**
     * ServiceDeletionService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface  $serverRepository
     * @param \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface $repository
     */
    public function __construct(
        ServerRepositoryInterface $serverRepository,
        ServiceRepositoryInterface $repository
    ) {
        $this->serverRepository = $serverRepository;
        $this->repository = $repository;
    }

    /**
     * Delete a service from the system only if there are no servers attached to it.
     *
     * @param int $service
     * @return int
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function handle($service)
    {
        $count = $this->serverRepository->findCountWhere([['service_id', '=', $service]]);
        if ($count > 0) {
            throw new HasActiveServersException(trans('exceptions.service.delete_has_servers'));
        }

        return $this->repository->delete($service);
    }
}
