<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Eggs;

use App\Contracts\Repository\EggRepositoryInterface;
use App\Exceptions\Service\Egg\HasChildrenException;
use App\Exceptions\Service\HasActiveServersException;
use App\Contracts\Repository\ServerRepositoryInterface;

class EggDeletionService
{
    /**
     * @var \App\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * EggDeletionService constructor.
     *
     * @param \App\Contracts\Repository\ServerRepositoryInterface $serverRepository
     * @param \App\Contracts\Repository\EggRepositoryInterface    $repository
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
     * @param int $egg
     * @return int
     *
     * @throws \App\Exceptions\Service\HasActiveServersException
     * @throws \App\Exceptions\Service\Egg\HasChildrenException
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
