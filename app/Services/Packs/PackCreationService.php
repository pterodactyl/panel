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

use Ramsey\Uuid\Uuid;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Contracts\Repository\PackRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Pterodactyl\Exceptions\Service\Pack\InvalidFileUploadException;
use Pterodactyl\Exceptions\Service\Pack\InvalidFileMimeTypeException;

class PackCreationService
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

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
     * @param \Illuminate\Contracts\Config\Repository                   $config
     * @param \Illuminate\Database\ConnectionInterface                  $connection
     * @param \Illuminate\Contracts\Filesystem\Factory                  $storage
     * @param \Pterodactyl\Contracts\Repository\PackRepositoryInterface $repository
     */
    public function __construct(
        ConfigRepository $config,
        ConnectionInterface $connection,
        FilesystemFactory $storage,
        PackRepositoryInterface $repository
    ) {
        $this->config = $config;
        $this->connection = $connection;
        $this->repository = $repository;
        $this->storage = $storage;
    }

    /**
     * Add a new service pack to the system.
     *
     * @param  array                              $data
     * @param  \Illuminate\Http\UploadedFile|null $file
     * @return \Pterodactyl\Models\Pack
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileMimeTypeException
     * @throws \Pterodactyl\Exceptions\Service\Pack\InvalidFileUploadException
     */
    public function handle(array $data, $file = null)
    {
        if (! is_null($file)) {
            if (! $file->isValid()) {
                throw new InvalidFileUploadException;
            }

            if (! in_array($file->getMimeType(), $this->config->get('pterodactyl.files.pack_types'))) {
                throw new InvalidFileMimeTypeException;
            }
        }

        // Transform values to boolean
        $data['selectable'] = isset($data['selectable']);
        $data['visible'] = isset($data['visible']);
        $data['locked'] = isset($data['locked']);

        $this->connection->beginTransaction();
        $pack = $this->repository->create(array_merge(
            ['uuid' => Uuid::uuid4()], $data
        ));

        $this->storage->disk('default')->makeDirectory('packs/' . $pack->uuid);
        if (! is_null($file)) {
            $file->storeAs('packs/' . $pack->uuid, 'archive.tar.gz');
        }

        $this->connection->commit();

        return $pack;
    }
}
