<?php

namespace Pterodactyl\Http\Requests\Api\Application\Eggs\Variables;

use Pterodactyl\Models\EggVariable;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreEggVariableRequest extends ApplicationApiRequest
{
    public function rules(array $rules = null): array
    {
        return [
            'name' => 'required|string|min:1|max:191',
            'description' => 'sometimes|string|nullable',
            'env_variable' => 'required|regex:/^[\w]{1,191}$/|notIn:' . EggVariable::RESERVED_ENV_NAMES,
            'default_value' => 'present',
            'user_viewable' => 'required|boolean',
            'user_editable' => 'required|boolean',
            'rules' => 'bail|required|string',
        ];
    }
}
