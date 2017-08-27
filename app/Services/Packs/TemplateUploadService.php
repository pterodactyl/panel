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

use ZipArchive;
use Illuminate\Http\UploadedFile;
use Pterodactyl\Exceptions\Service\Pack\ZipExtractionException;
use Pterodactyl\Exceptions\Service\Pack\InvalidFileUploadException;
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
     * @param int                           $option
     * @param \Illuminate\Http\UploadedFile $file
     * @return \Pterodactyl\Models\Pack
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Pack\ZipExtractionException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileUploadException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileMimeTypeException
     * @throws \Pterodactyl\Exceptions\Service\Pack\UnreadableZipArchiveException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidPackArchiveFormatException
     */
    public function handle($option, UploadedFile $file)
    {
        if (! $file->isValid()) {
            throw new InvalidFileUploadException(trans('admin/exceptions.packs.invalid_upload'));
        }

        if (! in_array($file->getMimeType(), self::VALID_UPLOAD_TYPES)) {
            throw new InvalidFileMimeTypeException(trans('admin/exceptions.packs.invalid_mime', [
                'type' => implode(', ', self::VALID_UPLOAD_TYPES),
            ]));
        }

        if ($file->getMimeType() === 'application/zip') {
            return $this->handleArchive($option, $file);
        } else {
            $json = json_decode($file->openFile()->fread($file->getSize()), true);
            $json['option_id'] = $option;

            return $this->creationService->handle($json);
        }
    }

    /**
     * Process a ZIP file to create a pack and stored archive.
     *
     * @param int                           $option
     * @param \Illuminate\Http\UploadedFile $file
     * @return \Pterodactyl\Models\Pack
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Pack\ZipExtractionException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileUploadException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileMimeTypeException
     * @throws \Pterodactyl\Exceptions\Service\Pack\UnreadableZipArchiveException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidPackArchiveFormatException
     */
    protected function handleArchive($option, $file)
    {
        if (! $this->archive->open($file->getRealPath())) {
            throw new UnreadableZipArchiveException(trans('admin/exceptions.packs.unreadable'));
        }

        if (! $this->archive->locateName('import.json') || ! $this->archive->locateName('archive.tar.gz')) {
            throw new InvalidPackArchiveFormatException(trans('admin/exceptions.packs.invalid_archive_exception'));
        }

        $json = json_decode($this->archive->getFromName('import.json'), true);
        $json['option_id'] = $option;

        $pack = $this->creationService->handle($json);
        if (! $this->archive->extractTo(storage_path('app/packs/' . $pack->uuid), 'archive.tar.gz')) {
            // @todo delete the pack that was created.
            throw new ZipExtractionException(trans('admin/exceptions.packs.zip_extraction'));
        }

        $this->archive->close();

        return $pack;
    }
}
