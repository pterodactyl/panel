<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Admin\Egg;

use Pterodactyl\Models\EggVariable;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class EggVariableFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|min:1|max:255',
            'description' => 'sometimes|nullable|string',
            'env_variable' => 'required|regex:/^[\w]{1,255}$/|notIn:' . EggVariable::RESERVED_ENV_NAMES,
            'default_value' => 'string',
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

        $validator->addRules(['default_value' => $rules]);
    }
}
