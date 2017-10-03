<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services\Options;

use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Exceptions\Service\ServiceOption\NoParentConfigurationFoundException;

class OptionUpdateService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $repository;

    /**
     * OptionUpdateService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface $repository
     */
    public function __construct(ServiceOptionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update a service option.
     *
     * @param int|\Pterodactyl\Models\ServiceOption $option
     * @param array                                 $data
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\ServiceOption\NoParentConfigurationFoundException
     */
    public function handle($option, array $data): void
    {
        if (! $option instanceof ServiceOption) {
            $option = $this->repository->find($option);
        }

        if (! is_null(array_get($data, 'config_from'))) {
            $results = $this->repository->findCountWhere([
                ['service_id', '=', $option->service_id],
                ['id', '=', array_get($data, 'config_from')],
            ]);

            if ($results !== 1) {
                throw new NoParentConfigurationFoundException(trans('exceptions.service.options.must_be_child'));
            }
        }

        $this->repository->withoutFresh()->update($option->id, $data);
    }
}
