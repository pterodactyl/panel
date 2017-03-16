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
use Pterodactyl\Models\Service;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class ServiceRepository
{
    /**
     * Creates a new service on the system.
     *
     * @param  array  $data
     * @return \Pterodactyl\Models\Service
     */
    public function create(array $data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|min:1|max:255',
            'description' => 'required|nullable|string',
            'folder' => 'required|unique:services,folder|regex:/^[\w.-]{1,50}$/',
            'startup' => 'required|nullable|string',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        return DB::transaction(function () use ($data) {
            $service = new Service;
            $service->author = config('pterodactyl.service.author');
            $service->fill([
                'name' => $data['name'],
                'description' => (isset($data['description'])) ? $data['description'] : null,
                'folder' => $data['folder'],
                'startup' => (isset($data['startup'])) ? $data['startup'] : null,
                'index_file' => Service::defaultIndexFile(),
            ])->save();

            // It is possible for an event to return false or throw an exception
            // which won't necessarily be detected by this transaction.
            //
            // This check ensures the model was actually saved.
            if (! $service->exists) {
                throw new \Exception('Service model was created however the response appears to be invalid. Did an event fire wrongly?');
            }

            return $service;
        });
    }

    /**
     * Updates a service.
     *
     * @param  int    $id
     * @param  array  $data
     * @return \Pterodactyl\Models\Service
     */
    public function update($id, array $data)
    {
        $service = Service::findOrFail($id);

        $validator = Validator::make($data, [
            'name' => 'sometimes|required|string|min:1|max:255',
            'description' => 'sometimes|required|nullable|string',
            'folder' => 'sometimes|required|regex:/^[\w.-]{1,50}$/',
            'startup' => 'sometimes|required|nullable|string',
            'index_file' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        return DB::transaction(function () use ($data, $service) {
            $service->fill($data)->save();

            return $service;
        });
    }

    /**
     * Deletes a service and associated files and options.
     *
     * @param  int   $id
     * @return void
     */
    public function delete($id)
    {
        $service = Service::withCount('servers')->with('options')->findOrFail($id);

        if ($service->servers_count > 0) {
            throw new DisplayException('You cannot delete a service that has servers associated with it.');
        }

        DB::transaction(function () use ($service) {
            foreach ($service->options as $option) {
                (new OptionRepository)->delete($option->id);
            }

            $service->delete();
        });
    }
}
