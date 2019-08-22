<?php

namespace App\Services\Eggs\Variables;

use App\Models\EggVariable;
use Illuminate\Support\Arr;
use App\Exceptions\DisplayException;
use Illuminate\Contracts\Validation\Factory;
use App\Traits\Services\ValidatesValidationRules;
use App\Contracts\Repository\EggVariableRepositoryInterface;
use App\Exceptions\Service\Egg\Variable\ReservedVariableNameException;

class VariableUpdateService
{
    use ValidatesValidationRules;

    /**
     * @var \App\Contracts\Repository\EggVariableRepositoryInterface
     */
    private $repository;

    /**
     * @var \Illuminate\Contracts\Validation\Factory
     */
    private $validator;

    /**
     * VariableUpdateService constructor.
     *
     * @param \App\Contracts\Repository\EggVariableRepositoryInterface $repository
     * @param \Illuminate\Contracts\Validation\Factory                         $validator
     */
    public function __construct(EggVariableRepositoryInterface $repository, Factory $validator)
    {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    /**
     * Return the validation factory instance to be used by rule validation
     * checking in the trait.
     *
     * @return \Illuminate\Contracts\Validation\Factory
     */
    protected function getValidator(): Factory
    {
        return $this->validator;
    }

    /**
     * Update a specific egg variable.
     *
     * @param \App\Models\EggVariable $variable
     * @param array                           $data
     * @return mixed
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Egg\Variable\ReservedVariableNameException
     */
    public function handle(EggVariable $variable, array $data)
    {
        if (! is_null(Arr::get($data, 'env_variable'))) {
            if (in_array(strtoupper(Arr::get($data, 'env_variable')), explode(',', EggVariable::RESERVED_ENV_NAMES))) {
                throw new ReservedVariableNameException(trans('exceptions.service.variables.reserved_name', [
                    'name' => Arr::get($data, 'env_variable'),
                ]));
            }

            $search = $this->repository->setColumns('id')->findCountWhere([
                ['env_variable', '=', $data['env_variable']],
                ['egg_id', '=', $variable->egg_id],
                ['id', '!=', $variable->id],
            ]);

            if ($search > 0) {
                throw new DisplayException(trans('exceptions.service.variables.env_not_unique', [
                    'name' => Arr::get($data, 'env_variable'),
                ]));
            }
        }

        if (! empty($data['rules'] ?? '')) {
            $this->validateRules($data['rules']);
        }

        $options = Arr::get($data, 'options') ?? [];

        return $this->repository->withoutFreshModel()->update($variable->id, [
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'env_variable' => $data['env_variable'] ?? '',
            'default_value' => $data['default_value'] ?? '',
            'user_viewable' => in_array('user_viewable', $options),
            'user_editable' => in_array('user_editable', $options),
            'rules' => $data['rules'] ?? '',
        ]);
    }
}
