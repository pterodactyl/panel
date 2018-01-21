<?php

namespace Pterodactyl\Http\Requests\Admin\Egg;

use Pterodactyl\Models\EggVariable;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class EggVariableFormRequest extends AdminFormRequest
{
    /**
     * Define rules for validation of this request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|min:1|max:255',
            'description' => 'sometimes|nullable|string',
            'env_variable' => 'required|regex:/^[\w]{1,255}$/|notIn:' . EggVariable::RESERVED_ENV_NAMES,
            'options' => 'sometimes|required|array',
            'rules' => 'bail|required|string',
        ];
    }

    /**
     * Run validation after the rules above have been applied.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator)
    {
        $rules = $this->input('rules');
        if ($this->method() === 'PATCH') {
            $rules = $this->input('rules', $this->route()->parameter('egg')->rules);
        }

        // If rules is not a string it is already violating the rule defined above
        // so just skip the addition of default value rules since this request
        // will fail anyways.
        if (! is_string($rules)) {
            return;
        }

        $validator->addRules(['default_value' => $rules]);
    }
}
