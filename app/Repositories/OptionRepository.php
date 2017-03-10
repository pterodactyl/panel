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
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class OptionRepository
{
    /**
     * Updates a service option in the database which can then be used
     * on nodes.
     *
     * @param  int    $id
     * @param  array  $data
     * @return \Pterodactyl\Models\ServiceOption
     */
    public function update($id, array $data)
    {
        $option = ServiceOption::findOrFail($id);

        $validator = Validator::make($data, [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'tag' => 'sometimes|required|string|max:255|unique:service_options,tag,' . $option->id,
            'docker_image' => 'sometimes|required|string|max:255',
            'startup' => 'sometimes|required|string',
            'config_from' => 'sometimes|required|numeric|exists:service_options,id',
        ]);

        $validator->sometimes('config_startup', 'required_without:config_from|json', function ($input) use ($option) {
            return ! (! $input->config_from && ! is_null($option->config_from));
        });

        $validator->sometimes('config_stop', 'required_without:config_from|string|max:255', function ($input) use ($option) {
            return ! (! $input->config_from && ! is_null($option->config_from));
        });

        $validator->sometimes('config_logs', 'required_without:config_from|json', function ($input) use ($option) {
            return ! (! $input->config_from && ! is_null($option->config_from));
        });

        $validator->sometimes('config_files', 'required_without:config_from|json', function ($input) use ($option) {
            return ! (! $input->config_from && ! is_null($option->config_from));
        });

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        $option->fill($data)->save();

        return $option;
    }
}
