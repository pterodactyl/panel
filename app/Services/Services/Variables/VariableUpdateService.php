<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services\Variables;

use Pterodactyl\Models\EggVariable;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface;
use Pterodactyl\Exceptions\Service\ServiceVariable\ReservedVariableNameException;

class VariableUpdateService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface
     */
    protected $repository;

    /**
     * VariableUpdateService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface $repository
     */
    public function __construct(ServiceVariableRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update a specific service variable.
     *
     * @param int|\Pterodactyl\Models\EggVariable $variable
     * @param array                               $data
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\ServiceVariable\ReservedVariableNameException
     */
    public function handle($variable, array $data)
    {
        if (! $variable instanceof EggVariable) {
            $variable = $this->repository->find($variable);
        }

        if (! is_null(array_get($data, 'env_variable'))) {
            if (in_array(strtoupper(array_get($data, 'env_variable')), explode(',', EggVariable::RESERVED_ENV_NAMES))) {
                throw new ReservedVariableNameException(trans('exceptions.service.variables.reserved_name', [
                    'name' => array_get($data, 'env_variable'),
                ]));
            }

            $search = $this->repository->withColumns('id')->findCountWhere([
                ['env_variable', '=', array_get($data, 'env_variable')],
                ['option_id', '=', $variable->option_id],
                ['id', '!=', $variable->id],
            ]);

            if ($search > 0) {
                throw new DisplayException(trans('exceptions.service.variables.env_not_unique', [
                    'name' => array_get($data, 'env_variable'),
                ]));
            }
        }

        $options = array_get($data, 'options', []);

        return $this->repository->withoutFresh()->update($variable->id, array_merge([
            'user_viewable' => in_array('user_viewable', $options),
            'user_editable' => in_array('user_editable', $options),
        ], $data));
    }
}
