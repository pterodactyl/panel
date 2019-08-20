<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Services\Packs;

use App\Models\Pack;
use Illuminate\Database\ConnectionInterface;
use App\Contracts\Repository\PackRepositoryInterface;
use App\Exceptions\Service\HasActiveServersException;
use App\Contracts\Repository\ServerRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

class PackDeletionService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \App\Contracts\Repository\PackRepositoryInterface
     */
    protected $repository;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Illuminate\Contracts\Filesystem\Factory
     */
    protected $storage;

    /**
     * PackDeletionService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                    $connection
     * @param \Illuminate\Contracts\Filesystem\Factory                    $storage
     * @param \App\Contracts\Repository\PackRepositoryInterface   $repository
     * @param \App\Contracts\Repository\ServerRepositoryInterface $serverRepository
     */
    public function __construct(
        ConnectionInterface $connection,
        FilesystemFactory $storage,
        PackRepositoryInterface $repository,
        ServerRepositoryInterface $serverRepository
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
        $this->storage = $storage;
    }

    /**
     * Delete a pack from the database as well as the archive stored on the server.
     *
     * @param  int|\App\Models\Pack$pack
     *
     * @throws \App\Exceptions\Service\HasActiveServersException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($pack)
    {
        if (! $pack instanceof Pack) {
            $pack = $this->repository->setColumns(['id', 'uuid'])->find($pack);
        }

        $count = $this->serverRepository->findCountWhere([['pack_id', '=', $pack->id]]);
        if ($count !== 0) {
            throw new HasActiveServersException(trans('exceptions.packs.delete_has_servers'));
        }

        $this->connection->beginTransaction();
        $this->repository->delete($pack->id);
        $this->storage->disk()->deleteDirectory('packs/' . $pack->uuid);
        $this->connection->commit();
    }
}
