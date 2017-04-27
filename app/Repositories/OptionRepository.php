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
     * Creates a new service option on the system.
     *
     * @param  array  $data
     * @return \Pterodactyl\Models\ServiceOption
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function create(array $data)
    {
        $validator = Validator::make($data, [
            'service_id' => 'required|numeric|exists:services,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'tag' => 'required|string|max:255|unique:service_options,tag',
            'docker_image' => 'required|string|max:255',
            'startup' => 'sometimes|nullable|string',
            'config_from' => 'sometimes|required|numeric|exists:service_options,id',
            'config_startup' => 'required_without:config_from|json',
            'config_stop' => 'required_without:config_from|string|max:255',
            'config_logs' => 'required_without:config_from|json',
            'config_files' => 'required_without:config_from|json',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        if (isset($data['config_from'])) {
            if (! ServiceOption::where('service_id', $data['service_id'])->where('id', $data['config_from'])->first()) {
                throw new DisplayException('The `configuration from` directive must be a child of the assigned service.');
            }
        }

        return ServiceOption::create($data);
    }

    /**
     * Deletes a service option from the system.
     *
     * @param  int  $id
     * @return void
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete($id)
    {
        $option = ServiceOption::with('variables')->withCount('servers')->findOrFail($id);

        if ($option->servers_count > 0) {
            throw new DisplayException('You cannot delete a service option that has servers associated with it.');
        }

        DB::transaction(function () use ($option) {
            foreach ($option->variables as $variable) {
                (new VariableRepository)->delete($variable->id);
            }

            $option->delete();
        });
    }

    /**
     * Updates a service option in the database which can then be used
     * on nodes.
     *
     * @param  int    $id
     * @param  array  $data
     * @return \Pterodactyl\Models\ServiceOption
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function update($id, array $data)
    {
        $option = ServiceOption::findOrFail($id);

        // Due to code limitations (at least when I am writing this currently)
        // we have to make an assumption that if config_from is not passed
        // that we should be telling it that no config is wanted anymore.
        //
        // This really is only an issue if we open API access to this function,
        // in which case users will always need to pass `config_from` in order
        // to keep it assigned.
        if (! isset($data['config_from']) && ! is_null($option->config_from)) {
            $option->config_from = null;
        }

        $validator = Validator::make($data, [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'tag' => 'sometimes|required|string|max:255|unique:service_options,tag,' . $option->id,
            'docker_image' => 'sometimes|required|string|max:255',
            'startup' => 'sometimes|required|string',
            'config_from' => 'sometimes|required|numeric|exists:service_options,id',
        ]);

        $validator->sometimes([
            'config_startup', 'config_logs', 'config_files',
        ], 'required_without:config_from|json', function ($input) use ($option) {
            return ! (! $input->config_from && ! is_null($option->config_from));
        });

        $validator->sometimes('config_stop', 'required_without:config_from|string|max:255', function ($input) use ($option) {
            return ! (! $input->config_from && ! is_null($option->config_from));
        });

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        if (isset($data['config_from'])) {
            if (! ServiceOption::where('service_id', $option->service_id)->where('id', $data['config_from'])->first()) {
                throw new DisplayException('The `configuration from` directive must be a child of the assigned service.');
            }
        }

        $option->fill($data)->save();

        return $option;
    }

    /**
     * Updates a service option's scripts in the database.
     *
     * @param  int    $id
     * @param  array  $data
     * @return \Pterodactyl\Models\ServiceOption
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function scripts($id, array $data)
    {
        $option = ServiceOption::findOrFail($id);

        $data['script_install'] = empty($data['script_install']) ? null : $data['script_install'];

        $validator = Validator::make($data, [
            'script_install' => 'sometimes|nullable|string',
            'script_is_privileged' => 'sometimes|required|boolean',
            'script_entry' => 'sometimes|required|string',
            'script_container' => 'sometimes|required|string',
            'copy_script_from' => 'sometimes|nullable|numeric',
        ]);

        if (isset($data['copy_script_from']) && ! empty($data['copy_script_from'])) {
            $select = ServiceOption::whereNull('copy_script_from')->where([
                ['id', $data['copy_script_from']],
                ['service_id', $option->service_id],
            ])->first();

            if (! $select) {
                throw new DisplayException('The service option selected to copy a script from either does not exist, or is copying from a higher level.');
            }
        } else {
            $data['copy_script_from'] = null;
        }

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        $option->fill($data)->save();

        return $option;
    }
}
