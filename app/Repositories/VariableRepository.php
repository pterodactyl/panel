<?php
/**
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

namespace Pterodactyl\Repositories;

use DB;
use Validator;
use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Models\ServiceVariable;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class VariableRepository
{
    /**
     * Create a new service variable.
     *
     * @param  int    $option
     * @param  array  $data
     * @return \Pterodactyl\Models\ServiceVariable
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function create($option, array $data)
    {
        $option = ServiceOption::select('id')->findOrFail($option);

        $validator = Validator::make($data, [
            'name' => 'required|string|min:1|max:255',
            'description' => 'sometimes|nullable|string',
            'env_variable' => 'required|regex:/^[\w]{1,255}$/',
            'default_value' => 'string',
            'options' => 'sometimes|required|array',
            'rules' => 'bail|required|string|min:1',
        ]);

        // Ensure the default value is allowed by the rules provided.
        $rules = (isset($data['rules'])) ? $data['rules'] : $variable->rules;
        $validator->sometimes('default_value', $rules, function ($input) {
            return $input->default_value;
        });

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        if (isset($data['env_variable'])) {
            $search = ServiceVariable::where('env_variable', $data['env_variable'])->where('option_id', $option->id);
            if ($search->first()) {
                throw new DisplayException('The envionment variable name assigned to this variable must be unique for this service option.');
            }
        }

        if (! isset($data['options']) || ! is_array($data['options'])) {
            $data['options'] = [];
        }

        $data['option_id'] = $option->id;
        $data['user_viewable'] = (in_array('user_viewable', $data['options']));
        $data['user_editable'] = (in_array('user_editable', $data['options']));
        $data['required'] = (in_array('required', $data['options']));

        // Remove field that isn't used.
        unset($data['options']);

        return ServiceVariable::create($data);
    }

    /**
     * Deletes a specified option variable as well as all server
     * variables currently assigned.
     *
     * @param  int    $id
     * @return void
     */
    public function delete($id)
    {
        $variable = ServiceVariable::with('serverVariable')->findOrFail($id);

        DB::transaction(function () use ($variable) {
            foreach ($variable->serverVariable as $v) {
                $v->delete();
            }

            $variable->delete();
        });
    }

    /**
     * Updates a given service variable.
     *
     * @param  int    $id
     * @param  array  $data
     * @return \Pterodactyl\Models\ServiceVariable
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function update($id, array $data)
    {
        $variable = ServiceVariable::findOrFail($id);

        $validator = Validator::make($data, [
            'name' => 'sometimes|required|string|min:1|max:255',
            'description' => 'sometimes|nullable|string',
            'env_variable' => 'sometimes|required|regex:/^[\w]{1,255}$/',
            'default_value' => 'string',
            'options' => 'sometimes|required|array',
            'rules' => 'bail|sometimes|required|string|min:1',
        ]);

        // Ensure the default value is allowed by the rules provided.
        $rules = (isset($data['rules'])) ? $data['rules'] : $variable->rules;
        $validator->sometimes('default_value', $rules, function ($input) {
            return $input->default_value;
        });

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        if (isset($data['env_variable'])) {
            $search = ServiceVariable::where('env_variable', $data['env_variable'])
                ->where('option_id', $variable->option_id)
                ->where('id', '!=', $variable->id);
            if ($search->first()) {
                throw new DisplayException('The envionment variable name assigned to this variable must be unique for this service option.');
            }
        }

        if (! isset($data['options']) || ! is_array($data['options'])) {
            $data['options'] = [];
        }

        $data['user_viewable'] = (in_array('user_viewable', $data['options']));
        $data['user_editable'] = (in_array('user_editable', $data['options']));
        $data['required'] = (in_array('required', $data['options']));

        // Remove field that isn't used.
        unset($data['options']);

        $variable->fill($data)->save();

        return $variable;
    }
}
