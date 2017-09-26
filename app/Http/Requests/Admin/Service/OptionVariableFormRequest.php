<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\ServiceVariable;

class OptionVariableFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|min:1|max:255',
            'description' => 'sometimes|nullable|string',
            'env_variable' => 'required|regex:/^[\w]{1,255}$/|notIn:' . ServiceVariable::RESERVED_ENV_NAMES,
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
            $rules = $this->input('rules', $this->route()->parameter('variable')->rules);
        }

        $validator->sometimes('default_value', $rules, function ($input) {
            return $input->default_value;
        });
    }
}
