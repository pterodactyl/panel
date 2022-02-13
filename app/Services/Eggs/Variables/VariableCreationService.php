<?php

namespace Pterodactyl\Services\Eggs\Variables;

use Pterodactyl\Models\EggVariable;
use Illuminate\Contracts\Validation\Factory as Validator;
use Pterodactyl\Traits\Services\ValidatesValidationRules;
use Pterodactyl\Exceptions\Service\Egg\Variable\ReservedVariableNameException;

class VariableCreationService
{
    use ValidatesValidationRules;

    private Validator $validator;

    /**
     * VariableCreationService constructor.
     */
    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Return the validation factory instance to be used by rule validation
     * checking in the trait.
     */
    protected function getValidator(): Validator
    {
        return $this->validator;
    }

    /**
     * Create a new variable for a given Egg.
     *
     * @throws \Pterodactyl\Exceptions\Service\Egg\Variable\BadValidationRuleException
     * @throws \Pterodactyl\Exceptions\Service\Egg\Variable\ReservedVariableNameException
     */
    public function handle(int $egg, array $data): EggVariable
    {
        if (in_array(strtoupper(array_get($data, 'env_variable')), explode(',', EggVariable::RESERVED_ENV_NAMES))) {
            throw new ReservedVariableNameException(sprintf('Cannot use the protected name %s for this environment variable.', array_get($data, 'env_variable')));
        }

        if (!empty($data['rules'] ?? '')) {
            $this->validateRules($data['rules']);
        }

        $options = array_get($data, 'options') ?? [];

        /** @var \Pterodactyl\Models\EggVariable $model */
        $model = EggVariable::query()->create([
            'egg_id' => $egg,
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'env_variable' => $data['env_variable'] ?? '',
            'default_value' => $data['default_value'] ?? '',
            'user_viewable' => $data['user_viewable'],
            'user_editable' => $data['user_editable'],
            'rules' => $data['rules'] ?? '',
        ]);

        return $model;
    }
}
