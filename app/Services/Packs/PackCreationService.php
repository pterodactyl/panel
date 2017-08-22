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

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Pterodactyl\Exceptions\Service\Pack\InvalidFileUploadException;
use Pterodactyl\Exceptions\Service\Pack\InvalidFileMimeTypeException;

class PackCreationService
{
    const VALID_UPLOAD_TYPES = [
        'application/gzip',
        'application/x-gzip',
    ];

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\PackRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Filesystem\Factory
     */
    protected $storage;

    /**
     * PackCreationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                  $connection
     * @param \Illuminate\Contracts\Filesystem\Factory                  $storage
     * @param \Pterodactyl\Contracts\Repository\PackRepositoryInterface $repository
     */
    public function __construct(
        ConnectionInterface $connection,
        FilesystemFactory $storage,
        PackRepositoryInterface $repository
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->storage = $storage;
    }

    /**
     * Add a new service pack to the system.
     *
     * @param array                              $data
     * @param \Illuminate\Http\UploadedFile|null $file
     * @return \Pterodactyl\Models\Pack
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileMimeTypeException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileUploadException
     */
    public function handle(array $data, UploadedFile $file = null)
    {
        if (! is_null($file)) {
            if (! $file->isValid()) {
                throw new InvalidFileUploadException(trans('admin/exceptions.packs.invalid_upload'));
            }

            if (! in_array($file->getMimeType(), self::VALID_UPLOAD_TYPES)) {
                throw new InvalidFileMimeTypeException(trans('admin/exceptions.packs.invalid_mime', [
                    'type' => implode(', ', self::VALID_UPLOAD_TYPES),
                ]));
            }
        }

        // Transform values to boolean
        $data['selectable'] = isset($data['selectable']);
        $data['visible'] = isset($data['visible']);
        $data['locked'] = isset($data['locked']);

        $this->connection->beginTransaction();
        $pack = $this->repository->create(array_merge(
            ['uuid' => Uuid::uuid4()],
            $data
        ));

        $this->storage->disk()->makeDirectory('packs/' . $pack->uuid);
        if (! is_null($file)) {
            $file->storeAs('packs/' . $pack->uuid, 'archive.tar.gz');
        }

        $this->connection->commit();

        return $pack;
    }
}
