<?php
/*
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

namespace Pterodactyl\Services\Packs;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Pterodactyl\Exceptions\Service\Pack\ZipArchiveCreationException;
use Pterodactyl\Models\Pack;
use ZipArchive;

class ExportPackService
{
    /**
     * @var \ZipArchive
     */
    protected $archive;

    /**
     * @var \Pterodactyl\Contracts\Repository\PackRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Filesystem\Factory
     */
    protected $storage;

    /**
     * ExportPackService constructor.
     *
     * @param \Illuminate\Contracts\Filesystem\Factory                  $storage
     * @param \Pterodactyl\Contracts\Repository\PackRepositoryInterface $repository
     * @param \ZipArchive                                               $archive
     */
    public function __construct(
        FilesystemFactory $storage,
        PackRepositoryInterface $repository,
        ZipArchive $archive
    ) {
        $this->archive = $archive;
        $this->repository = $repository;
        $this->storage = $storage;
    }

    /**
     * Prepare a pack for export.
     *
     * @param int|\Pterodactyl\Models\Pack $pack
     * @param bool                         $files
     * @return string
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Pack\ZipArchiveCreationException
     */
    public function handle($pack, $files = false)
    {
        if (! $pack instanceof Pack) {
            $pack = $this->repository->find($pack);
        }

        $json = [
            'name' => $pack->name,
            'version' => $pack->version,
            'description' => $pack->description,
            'selectable' => $pack->selectable,
            'visible' => $pack->visible,
            'locked' => $pack->locked,
        ];

        $filename = tempnam(sys_get_temp_dir(), 'pterodactyl_');
        if ($files) {
            if (! $this->archive->open($filename, $this->archive::CREATE)) {
                throw new ZipArchiveCreationException;
            }

            foreach ($this->storage->disk()->files('packs/' . $pack->uuid) as $file) {
                $this->archive->addFile(storage_path('app/' . $file), basename(storage_path('app/' . $file)));
            }

            $this->archive->addFromString('import.json', json_encode($json, JSON_PRETTY_PRINT));
            $this->archive->close();
        } else {
            $fp = fopen($filename, 'a+');
            fwrite($fp, json_encode($json, JSON_PRETTY_PRINT));
            fclose($fp);
        }

        return $filename;
    }
}
