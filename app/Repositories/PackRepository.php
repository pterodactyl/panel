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
use Uuid;
use Storage;
use Validator;
use Pterodactyl\Models\Pack;
use Pterodactyl\Services\UuidService;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class PackRepository
{
    /**
     * Creates a new pack on the system.
     *
     * @param  array $data
     * @return \Pterodactyl\Models\Pack
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function create(array $data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'version' => 'required|string',
            'description' => 'sometimes|nullable|string',
            'selectable' => 'sometimes|required|boolean',
            'visible' => 'sometimes|required|boolean',
            'locked' => 'sometimes|required|boolean',
            'option_id' => 'required|exists:service_options,id',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        if (isset($data['file_upload'])) {
            if (! $data['file_upload']->isValid()) {
                throw new DisplayException('The file provided does not appear to be valid.');
            }

            if (! in_array($data['file_upload']->getMimeType(), ['application/gzip', 'application/x-gzip'])) {
                throw new DisplayException('The file provided (' . $data['file_upload']->getMimeType() . ') does not meet the required filetype of application/gzip.');
            }
        }

        return DB::transaction(function () use ($data) {
            $uuid = new UuidService();

            $pack = new Pack;
            $pack->uuid = $uuid->generate('packs', 'uuid');
            $pack->fill([
                'option_id' => $data['option_id'],
                'name' => $data['name'],
                'version' => $data['version'],
                'description' => (empty($data['description'])) ? null : $data['description'],
                'selectable' => isset($data['selectable']),
                'visible' => isset($data['visible']),
                'locked' => isset($data['locked']),
            ])->save();

            if (! $pack->exists) {
                throw new DisplayException('Model does not exist after creation. Did an event prevent it from saving?');
            }

            Storage::makeDirectory('packs/' . $pack->uuid);
            if (isset($data['file_upload'])) {
                $data['file_upload']->storeAs('packs/' . $pack->uuid, 'archive.tar.gz');
            }

            return $pack;
        });
    }

    /**
     * Creates a new pack on the system given a template file.
     *
     * @param  array $data
     * @return \Pterodactyl\Models\Pack
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
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
            $pack = $this->create([
                'name' => $json->name,
                'version' => $json->version,
                'description' => $json->description,
                'option_id' => $data['option_id'],
                'selectable' => $json->selectable,
                'visible' => $json->visible,
                'locked' => $json->locked,
            ]);

            if (! $zip->extractTo(storage_path('app/packs/' . $pack->uuid), 'archive.tar.gz')) {
                $pack->delete();
                throw new DisplayException('Unable to extract the archive file to the correct location.');
            }

            $zip->close();

            return $pack;
        } else {
            $json = json_decode(file_get_contents($data['file_upload']->path()));

            return $this->create([
                'name' => $json->name,
                'version' => $json->version,
                'description' => $json->description,
                'option_id' => $data['option_id'],
                'selectable' => $json->selectable,
                'visible' => $json->visible,
                'locked' => $json->locked,
            ]);
        }
    }

    /**
     * Updates a pack on the system.
     *
     * @param  int   $id
     * @param  array $data
     * @return \Pterodactyl\Models\Pack
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function update($id, array $data)
    {
        $validator = Validator::make($data, [
            'name' => 'sometimes|required|string',
            'option_id' => 'sometimes|required|exists:service_options,id',
            'version' => 'sometimes|required|string',
            'description' => 'sometimes|string',
            'selectable' => 'sometimes|required|boolean',
            'visible' => 'sometimes|required|boolean',
            'locked' => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        $pack = Pack::withCount('servers')->findOrFail($id);

        if ($pack->servers_count > 0 && (isset($data['option_id']) && (int) $data['option_id'] !== $pack->option_id)) {
            throw new DisplayException('You cannot modify the associated option if servers are attached to a pack.');
        }

        $pack->fill([
            'name' => isset($data['name']) ? $data['name'] : $pack->name,
            'option_id' => isset($data['option_id']) ? $data['option_id'] : $pack->option_id,
            'version' => isset($data['version']) ? $data['version'] : $pack->version,
            'description' => (empty($data['description'])) ? null : $data['description'],
            'selectable' => isset($data['selectable']),
            'visible' => isset($data['visible']),
            'locked' => isset($data['locked']),
        ])->save();

        return $pack;
    }

    /**
     * Deletes a pack and files from the system.
     *
     * @param  int  $id
     * @return void
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete($id)
    {
        $pack = Models\Pack::withCount('servers')->findOrFail($id);

        if ($pack->servers_count > 0) {
            throw new DisplayException('Cannot delete a pack from the system if servers are assocaited with it.');
        }

        DB::transaction(function () use ($pack) {
            $pack->delete();
            Storage::deleteDirectory('packs/' . $pack->uuid);
        });
    }
}
