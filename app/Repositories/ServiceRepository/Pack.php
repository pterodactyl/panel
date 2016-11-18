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
use Storage;
use Uuid;
use Validator;

use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class Pack
{

    public function __construct()
    {
        //
    }

    public function create(array $data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'version' => 'required|string',
            'description' => 'sometimes|nullable|string',
            'option' => 'required|exists:service_options,id',
            'selectable' => 'sometimes|boolean',
            'visible' => 'sometimes|boolean',
            'build_memory' => 'required|integer|min:0',
            'build_swap' => 'required|integer|min:0',
            'build_cpu' => 'required|integer|min:0',
            'build_io' => 'required|integer|min:10|max:1000',
            'build_container' => 'required|string',
            'build_script' => 'sometimes|nullable|string'
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        if (isset($data['file_upload'])) {
            if (!$data['file_upload']->isValid()) {
                throw new DisplayException('The file provided does not appear to be valid.');
            }

            if (!in_array($data['file_upload']->getMimeType(), [
                'application/zip',
                'application/gzip'
            ])) {
                throw new DisplayException('The file provided does not meet the required filetypes of application/zip or application/gzip.');
            }
        }

        DB::beginTransaction();
        try {
            $uuid = new UuidService;
            $pack = Models\ServicePack::create([
                'option' => $data['option'],
                'uuid' => $uuid->generate('servers', 'uuid'),
                'build_memory' => $data['build_memory'],
                'build_swap' => $data['build_swap'],
                'build_cpu' => $data['build_swap'],
                'build_io' => $data['build_io'],
                'build_script' => (empty($data['build_script'])) ? null : $data['build_script'],
                'build_container' => $data['build_container'],
                'name' => $data['name'],
                'version' => $data['version'],
                'description' => (empty($data['description'])) ? null : $data['description'],
                'selectable' => isset($data['selectable']),
                'visible' => isset($data['visible'])
            ]);

            Storage::makeDirectory('packs/' . $pack->uuid);
            if (isset($data['file_upload'])) {
                $filename = ($data['file_upload']->getMimeType() === 'application/zip') ? 'archive.zip' : 'archive.tar.gz';
                $data['file_upload']->storeAs('packs/' . $pack->uuid, $filename);
            }

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }

        return $pack->id;
    }

    public function createWithTemplate(array $data)
    {
        if (!isset($data['file_upload'])) {
            throw new DisplayException('No template file was found submitted with this request.');
        }

        if (!$data['file_upload']->isValid()) {
            throw new DisplayException('The file provided does not appear to be valid.');
        }

        if (!in_array($data['file_upload']->getMimeType(), [
            'application/zip',
            'text/plain',
            'application/json'
        ])) {
            throw new DisplayException('The file provided (' . $data['file_upload']->getMimeType() . ') does not meet the required filetypes of application/zip or application/json.');
        }

        if ($data['file_upload']->getMimeType() === 'application/zip') {
            $zip = new \ZipArchive;
            if (!$zip->open($data['file_upload']->path())) {
                throw new DisplayException('The uploaded archive was unable to be opened.');
            }

            $isZip = $zip->locateName('archive.zip');
            $isTar = $zip->locateName('archive.tar.gz');

            if ($zip->locateName('import.json') === false || ($isZip === false && $isTar === false)) {
                throw new DisplayException('This contents of the provided archive were in an invalid format.');
            }

            $json = json_decode($zip->getFromName('import.json'));
            $id = $this->create([
                'name' => $json->name,
                'version' => $json->version,
                'description' => $json->description,
                'option' => $data['option'],
                'selectable' => $json->selectable,
                'visible' => $json->visible,
                'build_memory' => $json->build->memory,
                'build_swap' => $json->build->swap,
                'build_cpu' => $json->build->cpu,
                'build_io' => $json->build->io,
                'build_container' => $json->build->container,
                'build_script' => $json->build->script
            ]);

            $pack = Models\ServicePack::findOrFail($id);
            if (!$zip->extractTo(storage_path('app/packs/' . $pack->uuid), ($isZip === false) ? 'archive.tar.gz' : 'archive.zip')) {
                $pack->delete();
                throw new DisplayException('Unable to extract the archive file to the correct location.');
            }

            $zip->close();
            return $pack->id;
        } else {
            $json = json_decode(file_get_contents($data['file_upload']->path()));
            return $this->create([
                'name' => $json->name,
                'version' => $json->version,
                'description' => $json->description,
                'option' => $data['option'],
                'selectable' => $json->selectable,
                'visible' => $json->visible,
                'build_memory' => $json->build->memory,
                'build_swap' => $json->build->swap,
                'build_cpu' => $json->build->cpu,
                'build_io' => $json->build->io,
                'build_container' => $json->build->container,
                'build_script' => $json->build->script
            ]);
        }

    }

    public function update($id, array $data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'version' => 'required|string',
            'description' => 'string',
            'option' => 'required|exists:service_options,id',
            'selectable' => 'sometimes|boolean',
            'visible' => 'sometimes|boolean',
            'build_memory' => 'required|integer|min:0',
            'build_swap' => 'required|integer|min:0',
            'build_cpu' => 'required|integer|min:0',
            'build_io' => 'required|integer|min:10|max:1000',
            'build_container' => 'required|string',
            'build_script' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::transaction(function () use ($id, $data) {
            Models\ServicePack::findOrFail($id)->update([
                'option' => $data['option'],
                'build_memory' => $data['build_memory'],
                'build_swap' => $data['build_swap'],
                'build_cpu' => $data['build_swap'],
                'build_io' => $data['build_io'],
                'build_script' => (empty($data['build_script'])) ? null : $data['build_script'],
                'build_container' => $data['build_container'],
                'name' => $data['name'],
                'version' => $data['version'],
                'description' => (empty($data['description'])) ? null : $data['description'],
                'selectable' => isset($data['selectable']),
                'visible' => isset($data['visible'])
            ]);

            return true;
        });
    }

    public function delete($id) {
        $pack = Models\ServicePack::findOrFail($id);
        // @TODO Check for linked servers; foreign key should block this.
        DB::transaction(function () use ($pack) {
            $pack->delete();
            Storage::deleteDirectory('packs/' . $pack->uuid);
        });
    }

}
