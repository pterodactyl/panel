<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services\Options;

use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Exceptions\Service\ServiceOption\NoParentConfigurationFoundException;

class OptionCreationService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $repository;

    /**
     * CreationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface $repository
     */
    public function __construct(ServiceOptionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new service option and assign it to the given service.
     *
     * @param array $data
     * @return \Pterodactyl\Models\ServiceOption
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\ServiceOption\NoParentConfigurationFoundException
     */
    public function handle(array $data)
    {
        if (! is_null(array_get($data, 'config_from'))) {
            $results = $this->repository->findCountWhere([
                ['service_id', '=', array_get($data, 'service_id')],
                ['id', '=', array_get($data, 'config_from')],
            ]);

            if ($results !== 1) {
                throw new NoParentConfigurationFoundException(trans('exceptions.service.options.must_be_child'));
            }
        } else {
            $data['config_from'] = null;
        }

        return $this->repository->create($data);
    }
}
