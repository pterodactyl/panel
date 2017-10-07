<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services\Options;

use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Exceptions\Service\ServiceOption\HasChildrenException;

class OptionDeletionService
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
     * OptionDeletionService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $serverRepository
     * @param \Pterodactyl\Contracts\Repository\EggRepositoryInterface    $repository
     */
    public function __construct(
        ServerRepositoryInterface $serverRepository,
        EggRepositoryInterface $repository
    ) {
        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
    }

    /**
     * Delete an option from the database if it has no active servers attached to it.
     *
     * @param int $option
     * @return int
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     * @throws \Pterodactyl\Exceptions\Service\ServiceOption\HasChildrenException
     */
    public function handle(int $option): int
    {
        $servers = $this->serverRepository->findCountWhere([['option_id', '=', $option]]);
        if ($servers > 0) {
            throw new HasActiveServersException(trans('exceptions.service.options.delete_has_servers'));
        }

        $children = $this->repository->findCountWhere([['config_from', '=', $option]]);
        if ($children > 0) {
            throw new HasChildrenException(trans('exceptions.service.options.has_children'));
        }

        return $this->repository->delete($option);
    }
}
