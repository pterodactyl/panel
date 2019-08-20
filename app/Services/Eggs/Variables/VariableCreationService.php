<?php

namespace App\Services\Eggs\Variables;

use Illuminate\Support\Arr;
use App\Models\EggVariable;
use Illuminate\Contracts\Validation\Factory;
use App\Traits\Services\ValidatesValidationRules;
use App\Contracts\Repository\EggVariableRepositoryInterface;
use App\Exceptions\Service\Egg\Variable\ReservedVariableNameException;

class VariableCreationService
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
     * VariableCreationService constructor.
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
     * Create a new variable for a given Egg.
     *
     * @param int   $egg
     * @param array $data
     * @return \App\Models\EggVariable
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Service\Egg\Variable\BadValidationRuleException
     * @throws \App\Exceptions\Service\Egg\Variable\ReservedVariableNameException
     */
    public function handle(int $egg, array $data): EggVariable
    {
        if (in_array(strtoupper(Arr::get($data, 'env_variable')), explode(',', EggVariable::RESERVED_ENV_NAMES))) {
            throw new ReservedVariableNameException(sprintf(
                'Cannot use the protected name %s for this environment variable.',
                Arr::get($data, 'env_variable')
            ));
        }

        if (! empty($data['rules'] ?? '')) {
            $this->validateRules($data['rules']);
        }

        $options = Arr::get($data, 'options') ?? [];

        return $this->repository->create([
            'egg_id' => $egg,
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
