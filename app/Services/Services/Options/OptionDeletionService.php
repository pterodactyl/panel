<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services\Options;

use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;

class OptionDeletionService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * OptionDeletionService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface        $serverRepository
     * @param \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface $repository
     */
    public function __construct(
        ServerRepositoryInterface $serverRepository,
        ServiceOptionRepositoryInterface $repository
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
     */
    public function handle($option)
    {
        $servers = $this->serverRepository->findCountWhere([
            ['option_id', '=', $option],
        ]);

        if ($servers > 0) {
            throw new HasActiveServersException(trans('exceptions.service.options.delete_has_servers'));
        }

        return $this->repository->delete($option);
    }
}
