<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Packs;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Pterodactyl\Exceptions\Service\InvalidFileUploadException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
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
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException
     */
    public function handle(array $data, UploadedFile $file = null)
    {
        if (! is_null($file)) {
            if (! $file->isValid()) {
                throw new InvalidFileUploadException(trans('exceptions.packs.invalid_upload'));
            }

            if (! in_array($file->getMimeType(), self::VALID_UPLOAD_TYPES)) {
                throw new InvalidFileMimeTypeException(trans('exceptions.packs.invalid_mime', [
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
