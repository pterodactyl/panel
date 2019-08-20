<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Nests;

use App\Contracts\Repository\NestRepositoryInterface;
use App\Exceptions\Service\HasActiveServersException;
use App\Contracts\Repository\ServerRepositoryInterface;

class NestDeletionService
{
    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \App\Contracts\Repository\NestRepositoryInterface
     */
    protected $repository;

    /**
     * NestDeletionService constructor.
     *
     * @param \App\Contracts\Repository\ServerRepositoryInterface $serverRepository
     * @param \App\Contracts\Repository\NestRepositoryInterface   $repository
     */
    public function __construct(
        ServerRepositoryInterface $serverRepository,
        NestRepositoryInterface $repository
    ) {
        $this->serverRepository = $serverRepository;
        $this->repository = $repository;
    }

    /**
     * Delete a nest from the system only if there are no servers attached to it.
     *
     * @param int $nest
     * @return int
     *
     * @throws \App\Exceptions\Service\HasActiveServersException
     */
    public function handle(int $nest): int
    {
        $count = $this->serverRepository->findCountWhere([['nest_id', '=', $nest]]);
        if ($count > 0) {
            throw new HasActiveServersException(trans('exceptions.service.delete_has_servers'));
        }

        return $this->repository->delete($nest);
    }
}
