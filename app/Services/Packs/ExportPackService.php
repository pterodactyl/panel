<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Packs;

use ZipArchive;
use Pterodactyl\Models\Pack;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Pterodactyl\Exceptions\Service\Pack\ZipArchiveCreationException;

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
