<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Eggs\Scripts;

use Illuminate\Support\Arr;
use App\Models\Egg;
use App\Contracts\Repository\EggRepositoryInterface;
use App\Exceptions\Service\Egg\InvalidCopyFromException;

class InstallScriptService
{
    /**
     * @var \App\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * InstallScriptService constructor.
     *
     * @param \App\Contracts\Repository\EggRepositoryInterface $repository
     */
    public function __construct(EggRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Modify the install script for a given Egg.
     *
     * @param int|\App\Models\Egg $egg
     * @param array                       $data
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Egg\InvalidCopyFromException
     */
    public function handle($egg, array $data)
    {
        if (! $egg instanceof Egg) {
            $egg = $this->repository->find($egg);
        }

        if (! is_null(Arr::get($data, 'copy_script_from'))) {
            if (! $this->repository->isCopyableScript(Arr::get($data, 'copy_script_from'), $egg->nest_id)) {
                throw new InvalidCopyFromException(trans('exceptions.nest.egg.invalid_copy_id'));
            }
        }

        $this->repository->withoutFreshModel()->update($egg->id, [
            'script_install' => Arr::get($data, 'script_install'),
            'script_is_privileged' => Arr::get($data, 'script_is_privileged', 1),
            'script_entry' => Arr::get($data, 'script_entry'),
            'script_container' => Arr::get($data, 'script_container'),
            'copy_script_from' => Arr::get($data, 'copy_script_from'),
        ]);
    }
}
