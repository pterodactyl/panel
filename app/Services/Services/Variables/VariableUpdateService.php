<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Services\Services\Variables;

use Pterodactyl\Models\ServiceVariable;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface;
use Pterodactyl\Exceptions\Services\ServiceVariable\ReservedVariableNameException;

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
     * @param  int|\Pterodactyl\Models\ServiceVariable $variable
     * @param  array                                   $data
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Services\ServiceVariable\ReservedVariableNameException
     */
    public function handle($variable, array $data)
    {
        if (! $variable instanceof ServiceVariable) {
            $variable = $this->repository->find($variable);
        }

        if (! is_null(array_get($data, 'env_variable'))) {
            if (in_array(strtoupper(array_get($data, 'env_variable')), explode(',', ServiceVariable::RESERVED_ENV_NAMES))) {
                throw new ReservedVariableNameException(trans('admin/exceptions.service.variables.reserved_name', [
                    'name' => array_get($data, 'env_variable'),
                ]));
            }

            $search = $this->repository->withColumns('id')->findCountWhere([
                ['env_variable', '=', array_get($data, 'env_variable')],
                ['option_id', '=', $variable->option_id],
                ['id', '!=', $variable->id],
            ]);

            if ($search > 0) {
                throw new DisplayException(trans('admin/exceptions.service.variables.env_not_unique', [
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
