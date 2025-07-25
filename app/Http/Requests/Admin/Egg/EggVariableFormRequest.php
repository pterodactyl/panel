<?php

namespace Pterodactyl\Http\Requests\Admin\Egg;

use Pterodactyl\Models\EggVariable;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class EggVariableFormRequest extends AdminFormRequest
{
    /**
     * Define rules for validation of this request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:1|max:191',
            'description' => 'sometimes|nullable|string',
            'env_variable' => 'required|regex:/^[\w]{1,191}$/|notIn:' . EggVariable::RESERVED_ENV_NAMES,
            'options' => 'sometimes|required|array',
            'rules' => 'bail|required|string',
            'default_value' => 'present',
        ];
    }
}
