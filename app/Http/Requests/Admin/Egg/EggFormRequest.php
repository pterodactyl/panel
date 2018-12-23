<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Requests\Admin\Egg;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class EggFormRequest extends AdminFormRequest
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'docker_image' => 'required|string|max:255',
            'startup' => 'required|string',
            'config_from' => 'sometimes|bail|nullable|numeric',
            'config_stop' => 'required_without:config_from|nullable|string|max:255',
            'config_startup' => 'required_without:config_from|nullable|json',
            'config_logs' => 'required_without:config_from|nullable|json',
            'config_files' => 'required_without:config_from|nullable|json',
        ];

        if ($this->method() === 'POST') {
            $rules['nest_id'] = 'required|numeric|exists:nests,id';
        }

        return $rules;
    }

    /**
     * @param \Illuminate\Contracts\Validation\Validator $validator
     */
    public function withValidator($validator)
    {
        $validator->sometimes('config_from', 'exists:eggs,id', function () {
            return (int) $this->input('config_from') !== 0;
        });
    }
}
