<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Eggs;

use Illuminate\Support\Arr;
use App\Models\Egg;
use App\Contracts\Repository\EggRepositoryInterface;
use App\Exceptions\Service\Egg\NoParentConfigurationFoundException;

class EggUpdateService
{
    /**
     * @var \App\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * EggUpdateService constructor.
     *
     * @param \App\Contracts\Repository\EggRepositoryInterface $repository
     */
    public function __construct(EggRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update a service option.
     *
     * @param int|\App\Models\Egg $egg
     * @param array                       $data
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Egg\NoParentConfigurationFoundException
     */
    public function handle($egg, array $data)
    {
        if (! $egg instanceof Egg) {
            $egg = $this->repository->find($egg);
        }

        if (! is_null(Arr::get($data, 'config_from'))) {
            $results = $this->repository->findCountWhere([
                ['nest_id', '=', $egg->nest_id],
                ['id', '=', Arr::get($data, 'config_from')],
            ]);

            if ($results !== 1) {
                throw new NoParentConfigurationFoundException(trans('exceptions.nest.egg.must_be_child'));
            }
        }

        $this->repository->withoutFreshModel()->update($egg->id, $data);
    }
}
