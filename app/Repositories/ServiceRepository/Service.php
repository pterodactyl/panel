<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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
namespace Pterodactyl\Repositories\ServiceRepository;

use DB;
use Validator;

use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class Service
{

    public function __construct()
    {
        //
    }

    public function create(array $data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|min:1|max:255',
            'description' => 'required|string',
            'file' => 'required|regex:/^[\w.-]{1,50}$/',
            'executable' => 'required|max:255|regex:/^(.*)$/',
            'startup' => 'required|string'
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        if (Models\Service::where('file', $data['file'])->first()) {
            throw new DisplayException('A service using that configuration file already exists on the system.');
        }

        $service = new Models\Service;
        $service->fill($data);
        $service->save();

        return $service->id;
    }

    public function update($id, array $data)
    {
        $service = Models\Service::findOrFail($id);

        $validator = Validator::make($data, [
            'name' => 'sometimes|required|string|min:1|max:255',
            'description' => 'sometimes|required|string',
            'file' => 'sometimes|required|regex:/^[\w.-]{1,50}$/',
            'executable' => 'sometimes|required|max:255|regex:/^(.*)$/',
            'startup' => 'sometimes|required|string'
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        $service->fill($data);
        $service->save();
    }

}
