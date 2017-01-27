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

namespace Pterodactyl\Repositories\ServiceRepository;

use DB;
use Uuid;
use Storage;
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
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        if (isset($data['file_upload'])) {
            if (! $data['file_upload']->isValid()) {
                throw new DisplayException('The file provided does not appear to be valid.');
            }

            if (! in_array($data['file_upload']->getMimeType(), ['application/gzip', 'application/x-gzip'])) {
                throw new DisplayException('The file provided (' . $data['file_upload']->getMimeType() . ') does not meet the required filetype of application/gzip.');
            }
        }

        DB::beginTransaction();
        try {
            $uuid = new UuidService;
            $pack = Models\ServicePack::create([
                'option' => $data['option'],
                'uuid' => $uuid->generate('servers', 'uuid'),
                'name' => $data['name'],
                'version' => $data['version'],
                'description' => (empty($data['description'])) ? null : $data['description'],
                'selectable' => isset($data['selectable']),
                'visible' => isset($data['visible']),
            ]);

            Storage::makeDirectory('packs/' . $pack->uuid);
            if (isset($data['file_upload'])) {
                $data['file_upload']->storeAs('packs/' . $pack->uuid, 'archive.tar.gz');
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
        if (! isset($data['file_upload'])) {
            throw new DisplayException('No template file was found submitted with this request.');
        }

        if (! $data['file_upload']->isValid()) {
            throw new DisplayException('The file provided does not appear to be valid.');
        }

        if (! in_array($data['file_upload']->getMimeType(), [
            'application/zip',
            'text/plain',
            'application/json',
        ])) {
            throw new DisplayException('The file provided (' . $data['file_upload']->getMimeType() . ') does not meet the required filetypes of application/zip or application/json.');
        }

        if ($data['file_upload']->getMimeType() === 'application/zip') {
            $zip = new \ZipArchive;
            if (! $zip->open($data['file_upload']->path())) {
                throw new DisplayException('The uploaded archive was unable to be opened.');
            }

            $isTar = $zip->locateName('archive.tar.gz');

            if (! $zip->locateName('import.json') || ! $isTar) {
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
            ]);

            $pack = Models\ServicePack::findOrFail($id);
            if (! $zip->extractTo(storage_path('app/packs/' . $pack->uuid), 'archive.tar.gz')) {
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
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::transaction(function () use ($id, $data) {
            Models\ServicePack::findOrFail($id)->update([
                'option' => $data['option'],
                'name' => $data['name'],
                'version' => $data['version'],
                'description' => (empty($data['description'])) ? null : $data['description'],
                'selectable' => isset($data['selectable']),
                'visible' => isset($data['visible']),
            ]);

            return true;
        });
    }

    public function delete($id)
    {
        $pack = Models\ServicePack::findOrFail($id);
        // @TODO Check for linked servers; foreign key should block this.
        DB::transaction(function () use ($pack) {
            $pack->delete();
            Storage::deleteDirectory('packs/' . $pack->uuid);
        });
    }
}
