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
use Illuminate\Http\UploadedFile;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Pterodactyl\Exceptions\Service\Pack\ZipExtractionException;
use Pterodactyl\Exceptions\Service\Pack\InvalidFileMimeTypeException;
use Pterodactyl\Exceptions\Service\Pack\UnreadableZipArchiveException;
use Pterodactyl\Exceptions\Service\Pack\InvalidPackArchiveFormatException;

class TemplateUploadService
{
    const VALID_UPLOAD_TYPES = [
        'application/zip',
        'text/plain',
        'application/json',
    ];

    /**
     * @var \ZipArchive
     */
    protected $archive;

    /**
     * @var \Pterodactyl\Services\Packs\PackCreationService
     */
    protected $creationService;

    /**
     * TemplateUploadService constructor.
     *
     * @param \Pterodactyl\Services\Packs\PackCreationService $creationService
     * @param \ZipArchive                                     $archive
     */
    public function __construct(
        PackCreationService $creationService,
        ZipArchive $archive
    ) {
        $this->archive = $archive;
        $this->creationService = $creationService;
    }

    /**
     * Process an uploaded file to create a new pack from a JSON or ZIP format.
     *
     * @param int                           $egg
     * @param \Illuminate\Http\UploadedFile $file
     * @return \Pterodactyl\Models\Pack
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Pack\ZipExtractionException
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileMimeTypeException
     * @throws \Pterodactyl\Exceptions\Service\Pack\UnreadableZipArchiveException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidPackArchiveFormatException
     */
    public function handle($egg, UploadedFile $file)
    {
        if (! $file->isValid()) {
            throw new InvalidFileUploadException(trans('exceptions.packs.invalid_upload'));
        }

        if (! in_array($file->getMimeType(), self::VALID_UPLOAD_TYPES)) {
            throw new InvalidFileMimeTypeException(trans('exceptions.packs.invalid_mime', [
                'type' => implode(', ', self::VALID_UPLOAD_TYPES),
            ]));
        }

        if ($file->getMimeType() === 'application/zip') {
            return $this->handleArchive($egg, $file);
        } else {
            $json = json_decode($file->openFile()->fread($file->getSize()), true);
            $json['egg_id'] = $egg;

            return $this->creationService->handle($json);
        }
    }

    /**
     * Process a ZIP file to create a pack and stored archive.
     *
     * @param int                           $egg
     * @param \Illuminate\Http\UploadedFile $file
     * @return \Pterodactyl\Models\Pack
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Pack\ZipExtractionException
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileMimeTypeException
     * @throws \Pterodactyl\Exceptions\Service\Pack\UnreadableZipArchiveException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidPackArchiveFormatException
     */
    protected function handleArchive($egg, $file)
    {
        if (! $this->archive->open($file->getRealPath())) {
            throw new UnreadableZipArchiveException(trans('exceptions.packs.unreadable'));
        }

        if (! $this->archive->locateName('import.json') || ! $this->archive->locateName('archive.tar.gz')) {
            throw new InvalidPackArchiveFormatException(trans('exceptions.packs.invalid_archive_exception'));
        }

        $json = json_decode($this->archive->getFromName('import.json'), true);
        $json['egg_id'] = $egg;

        $pack = $this->creationService->handle($json);
        if (! $this->archive->extractTo(storage_path('app/packs/' . $pack->uuid), 'archive.tar.gz')) {
            // @todo delete the pack that was created.
            throw new ZipExtractionException(trans('exceptions.packs.zip_extraction'));
        }

        $this->archive->close();

        return $pack;
    }
}
