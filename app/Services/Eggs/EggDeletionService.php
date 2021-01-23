<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Eggs;

use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Exceptions\Service\Egg\HasChildrenException;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class EggDeletionService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * EggDeletionService constructor.
     */
    public function __construct(
        ServerRepositoryInterface $serverRepository,
        EggRepositoryInterface $repository
    ) {
        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
    }

    /**
     * Delete an Egg from the database if it has no active servers attached to it.
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     * @throws \Pterodactyl\Exceptions\Service\Egg\HasChildrenException
     */
    public function handle(int $egg): int
    {
        $servers = $this->serverRepository->findCountWhere([['egg_id', '=', $egg]]);
        if ($servers > 0) {
            throw new HasActiveServersException(trans('exceptions.nest.egg.delete_has_servers'));
        }

        $children = $this->repository->findCountWhere([['config_from', '=', $egg]]);
        if ($children > 0) {
            throw new HasChildrenException(trans('exceptions.nest.egg.has_children'));
        }

        return $this->repository->delete($egg);
    }
}
