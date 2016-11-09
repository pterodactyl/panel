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
use Uuid;
use Storage;

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
            'executable' => 'max:255|regex:/^(.*)$/',
            'startup' => 'string'
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        if (Models\Service::where('file', $data['file'])->first()) {
            throw new DisplayException('A service using that configuration file already exists on the system.');
        }

        $data['author'] = env('SERVICE_AUTHOR', (string) Uuid::generate(4));

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
            'executable' => 'sometimes|max:255|regex:/^(.*)$/',
            'startup' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        $service->fill($data);
        $service->save();
    }

    public function delete($id)
    {
        $service = Models\Service::findOrFail($id);
        $servers = Models\Server::where('service', $service->id)->get();
        $options = Models\ServiceOptions::select('id')->where('parent_service', $service->id);

        if (count($servers) !== 0) {
            throw new DisplayException('You cannot delete a service that has servers associated with it.');
        }

        DB::beginTransaction();
        try {
            Models\ServiceVariables::whereIn('option_id', $options->get()->toArray())->delete();
            $options->delete();
            $service->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function updateFile($id, array $data)
    {
        $service = Models\Service::findOrFail($id);

        $validator = Validator::make($data, [
            'file' => 'required|in:index,main',
            'contents' => 'required|string'
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        $filename = ($data['file'] === 'main') ? 'main.json' : 'index.js';
        $filepath = 'services/' . $service->file . '/' . $filename;
        $backup = 'services/.bak/' . str_random(12) . '.bak';

        DB::beginTransaction();

        try {
            Storage::move($filepath, $backup);
            Storage::put($filepath, $data['contents']);

            $checksum = Models\Checksum::firstOrNew([
                'service' => $service->id,
                'filename' => $filename
            ]);

            $checksum->checksum = sha1_file(storage_path('app/' . $filepath));
            $checksum->save();

            DB::commit();
        } catch(\Exception $ex) {
            DB::rollback();
            Storage::move($backup, $filepath);
            throw $ex;
        }

    }

}
