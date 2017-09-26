<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services;

use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;

class ServiceUpdateService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface
     */
    protected $repository;

    /**
     * ServiceUpdateService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface $repository
     */
    public function __construct(ServiceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update a service and prevent changing the author once it is set.
     *
     * @param int   $service
     * @param array $data
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($service, array $data)
    {
        if (! is_null(array_get($data, 'author'))) {
            unset($data['author']);
        }

        $this->repository->withoutFresh()->update($service, $data);
    }
}
