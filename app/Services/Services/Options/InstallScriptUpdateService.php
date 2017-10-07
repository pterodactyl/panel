<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services\Options;

use Pterodactyl\Models\Egg;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Exceptions\Service\ServiceOption\InvalidCopyFromException;

class InstallScriptUpdateService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * InstallScriptUpdateService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\EggRepositoryInterface $repository
     */
    public function __construct(EggRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Modify the option install script for a given service option.
     *
     * @param int|\Pterodactyl\Models\Egg $option
     * @param array                       $data
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\ServiceOption\InvalidCopyFromException
     */
    public function handle($option, array $data)
    {
        if (! $option instanceof Egg) {
            $option = $this->repository->find($option);
        }

        if (! is_null(array_get($data, 'copy_script_from'))) {
            if (! $this->repository->isCopiableScript(array_get($data, 'copy_script_from'), $option->service_id)) {
                throw new InvalidCopyFromException(trans('exceptions.service.options.invalid_copy_id'));
            }
        }

        $this->repository->withoutFresh()->update($option->id, [
            'script_install' => array_get($data, 'script_install'),
            'script_is_privileged' => array_get($data, 'script_is_privileged'),
            'script_entry' => array_get($data, 'script_entry'),
            'script_container' => array_get($data, 'script_container'),
            'copy_script_from' => array_get($data, 'copy_script_from'),
        ]);
    }
}
