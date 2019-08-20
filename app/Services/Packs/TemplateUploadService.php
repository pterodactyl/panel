<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Packs;

use ZipArchive;
use Illuminate\Http\UploadedFile;
use App\Exceptions\Service\InvalidFileUploadException;
use App\Exceptions\Service\Pack\ZipExtractionException;
use App\Exceptions\Service\Pack\InvalidFileMimeTypeException;
use App\Exceptions\Service\Pack\UnreadableZipArchiveException;
use App\Exceptions\Service\Pack\InvalidPackArchiveFormatException;

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
     * @var \App\Services\Packs\PackCreationService
     */
    protected $creationService;

    /**
     * TemplateUploadService constructor.
     *
     * @param \App\Services\Packs\PackCreationService $creationService
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
     * @return \App\Models\Pack
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Service\Pack\ZipExtractionException
     * @throws \App\Exceptions\Service\InvalidFileUploadException
     * @throws \App\Exceptions\Service\Pack\InvalidFileMimeTypeException
     * @throws \App\Exceptions\Service\Pack\UnreadableZipArchiveException
     * @throws \App\Exceptions\Service\Pack\InvalidPackArchiveFormatException
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
     * @return \App\Models\Pack
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Service\Pack\ZipExtractionException
     * @throws \App\Exceptions\Service\InvalidFileUploadException
     * @throws \App\Exceptions\Service\Pack\InvalidFileMimeTypeException
     * @throws \App\Exceptions\Service\Pack\UnreadableZipArchiveException
     * @throws \App\Exceptions\Service\Pack\InvalidPackArchiveFormatException
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
